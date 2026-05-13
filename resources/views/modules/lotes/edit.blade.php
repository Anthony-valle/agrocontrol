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

    <div class="modal-footer border-0 pt-0 px-4 pb-3 lote-create-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </div>
</form>

<style>
.lote-modal-body {
    padding-top: 0.4rem;
    padding-inline: 1.2rem !important;
    max-height: calc(100vh - 12.5rem);
    overflow-y: auto;
    overflow-x: hidden;
}

.lote-modal-body .row {
    --bs-gutter-x: 0.85rem;
    --bs-gutter-y: 0.35rem;
}

.lote-modal-body .form-label {
    margin-bottom: 0.15rem !important;
    font-size: 0.76rem;
    color: #4f5d5a;
    letter-spacing: 0.01em;
}

.lote-metric-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.08rem 0.55rem;
    align-items: center;
    padding-top: 0.08rem;
}

.lote-metric-list .map-manual-hint {
    flex-basis: 100%;
}

#map {
    width: 100%;
    height: clamp(290px, 44vh, 420px);
    min-height: 290px;
    max-height: 420px;
    border-radius: 10px;
    border: 1px solid #dbe5df;
    overflow: hidden;
}

.lote-modal-body .form-control,
.lote-modal-body .form-select {
    border-color: #d7dfeb;
    min-height: 32px;
    padding: 0.22rem 0.55rem;
    font-size: 0.82rem;
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
    line-height: 1.05;
    font-size: 0.67rem;
    margin-top: 0;
}

.lote-modal-body .map-unavailable-alert {
    border-radius: 10px;
    font-size: 0.78rem;
}

.lote-map-section {
    padding-top: 0.2rem;
}

.lote-map-actions {
    margin-top: 0.5rem !important;
}

.lote-map-actions .btn {
    min-width: 0;
    border-radius: 999px;
    padding: 0.28rem 0.6rem;
    font-size: 0.72rem;
}

.lote-modal-body .modal-footer,
.modal-footer {
    padding-top: 0.35rem !important;
    padding-bottom: 0.75rem !important;
}

.lote-create-footer {
    position: sticky;
    bottom: 0;
    z-index: 3;
    background: #fff;
    box-shadow: 0 -8px 18px rgba(15, 90, 67, 0.08);
    padding-inline: 1.2rem !important;
}

.lote-modal-body::-webkit-scrollbar {
    width: 9px;
}

.lote-modal-body::-webkit-scrollbar-track {
    background: #edf2ef;
    border-radius: 999px;
}

.lote-modal-body::-webkit-scrollbar-thumb {
    background: #b9cbc4;
    border-radius: 999px;
    border: 2px solid #edf2ef;
}

.lote-modal-body::-webkit-scrollbar-thumb:hover {
    background: #8eaba0;
}

.modal-footer .btn {
    padding: 0.28rem 0.6rem;
    font-size: 0.72rem;
}

@media (max-width: 600px) {
    #map {
        height: 34vh;
        min-height: 240px;
    }

    .lote-modal-body {
        padding-inline: 0.9rem;
        max-height: calc(100vh - 10.5rem);
    }

    .lote-modal-body .row {
        --bs-gutter-x: 0.65rem;
        --bs-gutter-y: 0.55rem;
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