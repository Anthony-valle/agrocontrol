<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Importar Insumos desde Excel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('insumos.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file" class="form-label">Archivo Excel</label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                    <small class="text-muted">El archivo debe tener columnas: categoria_id, codigo, nombres, ingredientes_activo, unidad_medida, costo_estimado</small>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Importar</button>
                </div>
            </form>
        </div>
    </div>
</div>