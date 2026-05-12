<div class="modal-header bg-primary text-white border-0">
    <h5 class="modal-title fw-semibold">Importar Excel</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<form action="{{ route('insumos.importar.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal-body px-4 py-3 agro-modal-body">
        <p class="fw-semibold mb-3">Ejemplo de columnas y datos:</p>

        <!-- Tabla de ejemplo adaptada a tu modelo -->
        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>codigo</th>
                        <th>nombre</th>
                        <th>ingrediente_activo</th>
                        <th>categoria_nombre</th>
                        <th>unidad_medida</th>
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
                        <td>INS-0001</td>
                        <td>Urea 46%</td>
                        <td>Nitrógeno</td>
                        <td>Fertilizante</td>
                        <td>Kg</td>
                        <td>100</td>
                        <td>1</td>
                        <td>1</td>
                        <td>L-UREA-01</td>
                        <td>250</td>
                        <td>590</td>
                    </tr>
                    <tr>
                        <td>INS-0002</td>
                        <td>Fosfato</td>
                        <td>Fósforo</td>
                        <td>Fertilizante</td>
                        <td>Kg</td>
                        <td>50</td>
                        <td>1</td>
                        <td>1</td>
                        <td>L-FOS-01</td>
                        <td>120</td>
                        <td>715</td>
                    </tr>
                    <tr>
                        <td>INS-0003</td>
                        <td>Herbicida</td>
                        <td>Glifosato</td>
                        <td>Fitosanitario</td>
                        <td>L</td>
                        <td>30</td>
                        <td>1</td>
                        <td>1</td>
                        <td>L-HER-01</td>
                        <td>40</td>
                        <td>220</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mb-3">
            <label for="archivo_excel" class="form-label">Seleccione el archivo Excel</label>
            <input type="file" class="form-control" name="archivo_excel" accept=".xlsx,.xls,.csv" required>
            @error('archivo_excel')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="alert alert-info border-0 rounded-3">
            <small>
                Puede usar los encabezados del ejemplo o estas variantes aceptadas: <b>unidad_medida_base</b>, <b>categoria</b>, <b>bodega</b>, <b>cantidad</b>.
                <br>
                La columna de bodega acepta <b>ID</b>, <b>codigo</b> o <b>nombre</b>.
                <br>
                Las columnas obligatorias son: <b>codigo, nombre, unidad_medida</b> (o <b>unidad_medida_base</b>).
                <br>
                Si el <b>codigo</b> ya existe en el catalogo, para carga historica puede omitir <b>nombre/unidad/categoria</b> y solo cargar existencias por bodega/lote.
                <br>
                La columna <b>categoria_nombre</b> acepta cualquier categoria no vacia (ej: Fertilizante, Fitosanitario, Combustible, Indirectos, CIF, etc.).
                <br>
                Para que los insumos aparezcan disponibles en consumo desde el inicio, agregue también: <b>bodega_id, stock_inicial</b>.
                <br>
                Si el mismo <b>insumo + bodega + numero_lote</b> ya existe, la importación <b>suma stock</b> al inventario actual y conserva relación con catálogo e inventario.
                <br>
                Cada fila con cantidad mayor a 0 también registra un movimiento tipo <b>ENTRADA</b> en kardex (movimiento_inventarios).
                <br>
                Además se crea su comprobante en <b>factura_inventarios</b> (sin archivo adjunto) para mantener todas las relaciones.
                <br>
                Columnas opcionales: ingrediente_activo, stock_minimo, estado, numero_lote, costo_promedio, proveedor, referencia, fecha_fabricacion, fecha_vencimiento.
            </small>
        </div>
    </div>

    <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Importar
        </button>
    </div>
</form>

<style>
.agro-modal-body .form-control,
.agro-modal-body .form-select {
    border-color: #d7dfeb;
}
</style>