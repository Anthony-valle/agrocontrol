<div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header text-white" style="background-color: #2d5a27;">
            <h5 class="modal-title" id="miModalFormularioLabel">
                <i class="bi bi-plus-circle me-1"></i> Agregar Nuevo Producto
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body p-4"> <form action="{{ route('productos.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Código SAP</label>
                        <input type="text" class="form-control" name="codigo_sap" placeholder="10000045">
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label fw-bold">Nombre del Producto</label>
                        <input type="text" class="form-control" name="nombre_producto" placeholder="Ej: Urea" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Ingrediente Activo</label>
                        <input type="text" class="form-control" name="ingrediente_activo" placeholder="Ej: Nitrógeno">
                    </div>
                     <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Categoría</label>
                        <select class="form-select" name="categoria" required>
                            <option value="" selected disabled>Seleccione...</option>
                            <option value="Fertilizantes">Fertilizantes</option>
                            <option value="Plaguicidas">Plaguicidas</option>
                            <option value="Herbicidas">Herbicidas</option>
                            <option value="Insecticidas">Insecticidas</option>
                            <option value="Semillas">Semillas</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Unidad Medida</label>
                        <input type="text" class="form-control" name="unidad_medida" placeholder="Ej: Kg, L">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Cantidad</label>
                        <input type="number" class="form-control" name="cantidad" min="1" placeholder="Ej: 50">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Lote de Fabricación</label>
                        <input type="text" class="form-control" name="lote" placeholder="Ej: LT-03">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Precio de Compra</label>
                        <div class="input-group">
                            <span class="input-group-text">Lps</span>
                            <input type="number" class="form-control" name="precio_compra" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Proveedor</label>
                        <input type="text" class="form-control" name="proveedor" placeholder="Ej: Agroinsumos S.A.">
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label fw-bold">Número de Factura</label>
                        <input type="text" class="form-control" name="numero_factura" placeholder="FAC-001245">
                    </div>
                      <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Fecha de Vencimiento</label>
                        <input type="date" class="form-control" name="fecha_vencimiento">
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">Factura/ Anexo</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-image"></i></span>
                            <input type="file" class="form-control" name="imagen_producto" accept="image/*">
                        </div>
                        <small class="text-muted">Formatos permitidos: JPG, PNG. Máx 2MB.</small>
                    </div>
                </div>

                <div class="modal-footer border-top-0 d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success px-5">
                        <i class="bi bi-save me-1"></i> Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>