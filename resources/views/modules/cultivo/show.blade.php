<div class="modal-header bg-info text-white">
    <h5 class="modal-title">
        <i class="fa-solid fa-seedling me-2"></i> Detalle del Cultivo
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body" style="color: black">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label text-muted">Codigo</label>
            <div class="form-control bg-light">{{ $cultivo->codigo ?? '-' }}</div>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted">Nombre</label>
            <div class="form-control bg-light">{{ $cultivo->nombre ?? '-' }}</div>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted">Lote</label>
            <div class="form-control bg-light">{{ $cultivo->lote->nombre ?? '-' }}</div>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted">Variedad</label>
            <div class="form-control bg-light">{{ $cultivo->variedad ?? '-' }}</div>
        </div>
        <div class="col-md-2">
            <label class="form-label text-muted">Ciclo</label>
            <div class="form-control bg-light">{{ $cultivo->ciclo ?? '-' }}</div>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted">Fecha siembra</label>
            <div class="form-control bg-light">{{ $cultivo->fecha_siembra ? \Carbon\Carbon::parse($cultivo->fecha_siembra)->format('d/m/Y') : '-' }}</div>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted">Fecha cosecha</label>
            <div class="form-control bg-light">{{ $cultivo->fecha_cosecha ? \Carbon\Carbon::parse($cultivo->fecha_cosecha)->format('d/m/Y') : '-' }}</div>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted">Hectareas</label>
            <div class="form-control bg-light">{{ $cultivo->hectareas ?? '-' }}</div>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted">Cosecha estimada</label>
            <div class="form-control bg-light">{{ $cultivo->cosecha_estimada ?? '-' }}</div>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted">Unidad</label>
            <div class="form-control bg-light">{{ $cultivo->unidad_medida ?? '-' }}</div>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted">Estado</label>
            <div class="form-control bg-light">{{ $cultivo->estado ?? '-' }}</div>
        </div>
        <div class="col-8">
            <label class="form-label text-muted">Observaciones</label>
            <div class="form-control bg-light" style="min-height: 80px;">{{ $cultivo->observaciones ?: 'Sin observaciones.' }}</div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>