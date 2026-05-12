<div class="modal-header bg-primary text-white border-0">
    <h5 class="modal-title fw-semibold">
        <i class="bi bi-file-earmark-excel me-2"></i> Carga Masiva de Entrada Inicial (Stock Inicial)
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<form action="{{ route('movimientos.entrada.importar.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal-body px-4 py-3 agro-modal-body">
        <p class="fw-semibold mb-3">Plantilla Excel para Entrada Inicial con stock_inicial:</p>

        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>codigo</th>
                        <th>nombre</th>
                        <th>ingrediente_activo</th>
                        <th>categoria_nombre</th>
                        <th>Unidad medida base</th>
                        <th>stock_minimo</th>
                        <th>estado</th>
                        <th>bodega_id</th>
                        <th>numero_lote</th>
                        <th>stock_inicial</th>
                        <th>costo_promedio</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>4000680</td>
                        <td>NATIVO 75 WG</td>
                        <td>TEBUCONAZOLE + TRIFLOXYSTROBIN WG 75</td>
                        <td>Fitosanitario</td>
                        <td>Kg</td>
                        <td>5</td>
                        <td>1</td>
                        <td>1</td>
                        <td>LG64000821</td>
                        <td>0.010</td>
                        <td>4892.930</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mb-3">
            <label for="archivo_excel" class="form-label">Seleccione el archivo Excel</label>
            <input type="file" class="form-control" name="archivo_excel" accept=".xlsx,.xls,.csv" required>
        </div>

        <div class="alert alert-info mb-0 border-0 rounded-3">
            <small>
                Esta carga masiva crea/actualiza el <b>catalogo de insumos</b>, crea automaticamente el <b>catalogo de categorias</b> si una categoria no existe, actualiza <b>inventario_bodegas</b>, registra <b>movimiento_inventarios</b> tipo ENTRADA y genera comprobante en <b>factura_inventarios</b>.
                <br>
                La columna <b>bodega_id</b> puede venir como <b>ID</b>, <b>codigo</b> o <b>nombre</b> de bodega.
                <br>
                La columna <b>Unidad medida base</b> tambien se acepta como <b>unidad_medida</b>.
                <br>
                La columna <b>stock_inicial</b> es la cantidad de entrada inicial por fila (puede ser 0 si solo desea crear/catalogar lote).
                <br>
                La columna <b>categoria_nombre</b> acepta cualquier categoria no vacia (ej: Fertilizante, Fitosanitario, Combustible, Indirectos, CIF, etc.) y si no existe en catalogo se crea automaticamente.
                <br>
                Si el mismo <b>insumo + bodega + numero_lote</b> ya existe, la importación suma stock al lote actual.
                <br>
                La plantilla descargable usa exactamente este formato de <b>11 columnas</b> en un archivo <b>Excel .xlsx</b>. El importador también acepta archivos que lleguen en una sola celda con <b>,</b>, <b>;</b> o tabulación.
            </small>
        </div>
    </div>

    <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="{{ route('movimientos.entrada.importar.template') }}" class="btn btn-outline-success">
            <i class="bi bi-download me-1"></i> Descargar plantilla Excel
        </a>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-upload me-1"></i> Importar Entrada Inicial por Stock
        </button>
    </div>
</form>

<style>
.agro-modal-body .form-control,
.agro-modal-body .form-select {
    border-color: #d7dfeb;
}
</style>
