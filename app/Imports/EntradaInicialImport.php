<?php

namespace App\Imports;

use App\Models\Bodega;
use App\Models\Categorias;
use App\Models\FacturaInventario;
use App\Models\Insumo;
use App\Models\InventarioBodega;
use App\Models\MovimientoInventario;
use App\Services\EntradaLegacySyncService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\RemembersChunkOffset;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class EntradaInicialImport implements ToCollection, SkipsEmptyRows, WithChunkReading
{
    use RemembersChunkOffset;

    private const PLANTILLA_INDICES = [
        'codigo' => 0,
        'nombre' => 1,
        'ingrediente_activo' => 2,
        'categoria_nombre' => 3,
        'unidad_medida' => 4,
        'stock_minimo' => 5,
        'estado' => 6,
        'bodega_id' => 7,
        'numero_lote' => 8,
        'stock_inicial' => 9,
        'costo_promedio' => 10,
        'fecha_fabricacion' => 11,
        'fecha_vencimiento' => 12,
        'proveedor' => 13,
    ];

    protected int $userId;
    protected ?int $defaultSucursalId;
    protected ?int $defaultEmpresaId;
    protected array $bodegaCache = [];
    protected array $categoriaCache = [];
    protected array $insumoCache = [];
    protected array $inventarioCache = [];
    protected ?bool $bodegasTieneCodigo = null;
    protected ?array $headerMap = null;
    protected ?int $headerRowOffset = null;
    protected ?int $inferredBodegaIndex = null;
    protected array $columnasTablaCache = [];
    protected ?bool $insumosTieneSucursalId = null;
    protected EntradaLegacySyncService $entradaLegacySyncService;

    protected array $stats = [
        'filas_procesadas' => 0,
        'filas_importadas' => 0,
        'filas_error' => 0,
        'insumos_creados' => 0,
        'insumos_actualizados' => 0,
        'lotes_creados_o_actualizados' => 0,
        'movimientos_creados' => 0,
    ];

    protected array $errores = [];

    public function __construct(int $userId, ?int $defaultSucursalId = null, ?int $defaultEmpresaId = null)
    {
        $this->userId = $userId;
        $this->defaultSucursalId = $defaultSucursalId;
        $this->defaultEmpresaId = $defaultEmpresaId;
        $this->entradaLegacySyncService = app(EntradaLegacySyncService::class);
    }

    public function collection(Collection $rows): void
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        if ($rows->isEmpty()) {
            return;
        }

        $rows = $rows
            ->map(fn ($row) => $this->expandirFilaSeparada($row))
            ->filter(function (Collection $row) {
                if ($row->isEmpty()) {
                    return false;
                }

                $primerValor = trim((string) $row->first());

                return $primerValor !== '' && ! in_array(strtolower($primerValor), ['sep=;', 'sep=,', "sep=\t"], true);
            })
            ->values();

        if ($this->headerMap === null && $this->esPrimerChunk()) {
            foreach ($rows->take(25) as $indice => $filaCandidata) {
                if ($filaCandidata instanceof Collection) {
                    $filaCandidata = $filaCandidata->toArray();
                }

                $mapaCabecera = $this->resolverMapaCabeceras((array) $filaCandidata);
                if ($mapaCabecera === null) {
                    continue;
                }

                $this->headerMap = $mapaCabecera;
                $this->headerRowOffset = $indice;
                $rows = $rows->slice($indice + 1)->values();
                break;
            }
        }

        if ($this->headerMap === null && $this->inferredBodegaIndex === null) {
            $this->inferredBodegaIndex = $this->inferirIndiceBodega($rows);
        }

        foreach ($rows as $index => $row) {
            if ($index % 200 === 0) {
                @set_time_limit(0);
            }

            $this->stats['filas_procesadas']++;
            $fila = $this->getCurrentChunkOffset() + $index + 1;

            if ($this->esPrimerChunk()) {
                $fila += (int) ($this->headerRowOffset ?? 0);
            }

            try {
                DB::transaction(function () use ($row, $fila) {
                    $this->procesarFila($row, $fila);
                });
            } catch (\Throwable $error) {
                $this->registrarError($fila, $error->getMessage());
            }
        }
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getErrores(): array
    {
        return array_slice($this->errores, 0, 30);
    }

    public function getSummaryLines(): array
    {
        return [
            'Filas procesadas: ' . $this->stats['filas_procesadas'],
            'Filas importadas: ' . $this->stats['filas_importadas'],
            'Filas con error: ' . $this->stats['filas_error'],
            'Insumos creados: ' . $this->stats['insumos_creados'],
            'Insumos actualizados: ' . $this->stats['insumos_actualizados'],
            'Lotes creados/actualizados: ' . $this->stats['lotes_creados_o_actualizados'],
            'Movimientos creados: ' . $this->stats['movimientos_creados'],
        ];
    }

    protected function procesarFila(Collection $row, int $fila): void
    {
        $row = $this->expandirFilaSeparada($row);
        $row = $this->normalizarFila($row);

        if ($this->esFilaCabecera($row)) {
            return;
        }

        $usaFormatoPlantilla = $this->filaCoincideConPlantilla($row);

        [$bodegaRef, $bodegaColumnaDetectada, $bodegaIndiceDetectado] = $this->resolverReferenciaBodega($row);

        $codigo = $this->obtenerValorFlexible(
            $row,
            ['codigo', 'cod', 'codigo_insumo', 'insumo_codigo', 'sku', 'item_code', 'product_code'],
            $this->indicesFallback($usaFormatoPlantilla, 'codigo', [0])
        );
        $nombre = $this->obtenerValorFlexible(
            $row,
            ['nombre', 'descripcion', 'producto', 'insumo', 'nombre_producto', 'descripcion_producto', 'descripcion_insumo'],
            $this->indicesFallback($usaFormatoPlantilla, 'nombre', [1])
        );
        $categoria = $this->obtenerValorFlexible(
            $row,
            ['categoria_nombre', 'categoria', 'categoria_insumo', 'familia', 'grupo'],
            $this->indicesFallback($usaFormatoPlantilla, 'categoria_nombre', [3])
        );
        $unidad = $this->obtenerValorFlexible(
            $row,
            ['unidad_medida', 'unidad', 'um', 'unidad_de_medida', 'unidadmedida', 'unidad_medida_base', 'unidad medida base'],
            $this->indicesFallback($usaFormatoPlantilla, 'unidad_medida', [4])
        );

        if (!$codigo || !$nombre || !$categoria || !$unidad || !$bodegaRef) {
            $faltantes = [];
            if (!$codigo) $faltantes[] = 'codigo';
            if (!$nombre) $faltantes[] = 'nombre';
            if (!$categoria) $faltantes[] = 'categoria_nombre';
            if (!$unidad) $faltantes[] = 'unidad_medida';
            if (!$bodegaRef) $faltantes[] = 'bodega_id';
            throw new \RuntimeException('Faltan columnas o valores obligatorios: ' . implode(', ', $faltantes) . ". Valor bodega detectado: '" . ($bodegaRef ?? 'NULO') . "', columna detectada: '" . ($bodegaColumnaDetectada ?? 'NO DETECTADA') . "'.");
        }

        $bodega = $this->resolverBodega($bodegaRef);
        if (!$bodega) {
            throw new \RuntimeException('No se encontró la bodega indicada. Valor recibido: ' . var_export($bodegaRef, true) . '. Columna detectada: ' . ($bodegaColumnaDetectada ?? 'NO DETECTADA') . '. Verifique que el valor coincida con el ID, código o nombre de una bodega existente.');
        }

        $stockInicial = round($this->toFloat($this->obtenerValorFlexible(
            $row,
            ['stock_inicial', 'stock', 'stock_actual', 'cantidad', 'cantidad_inicial', 'existencia', 'inventario_inicial'],
            $this->indicesFallback($usaFormatoPlantilla, 'stock_inicial', $this->resolverIndicesRelativos($bodegaIndiceDetectado, [2, 9, 10, 11]))
        )), 3);
        $costoPromedio = round($this->toFloat($this->obtenerValorFlexible(
            $row,
            [
                'costo_promedio',
                'costo_prom',
                'costo_prome',
                'costo_promed',
                'costo promedio',
                'costo prom',
                'precio_promedio',
                'precio promedio',
                'costo',
                'precio',
                'costo_unitario',
                'precio_unitario',
                'valor_unitario',
            ],
            $this->indicesFallback($usaFormatoPlantilla, 'costo_promedio', $this->resolverIndicesRelativos($bodegaIndiceDetectado, [3, 10, 11, 12]))
        )), 4);
        $stockMinimo = round($this->toFloat($this->obtenerValorFlexible(
            $row,
            ['stock_minimo', 'minimo', 'min_stock'],
            $this->indicesFallback($usaFormatoPlantilla, 'stock_minimo', [5])
        )), 3);
        $estado = $this->toBool($this->obtenerValorFlexible(
            $row,
            ['estado', 'activo', 'ind_activo'],
            $this->indicesFallback($usaFormatoPlantilla, 'estado', [6])
        ), true);
        $numeroLote = $this->obtenerValorFlexible(
            $row,
            ['numero_lote', 'lote', 'lote_numero', 'numero_de_lote'],
            $this->indicesFallback($usaFormatoPlantilla, 'numero_lote', $this->resolverIndicesRelativos($bodegaIndiceDetectado, [1, 8, 9, 10]))
        );
        $proveedor = $this->obtenerValorFlexible(
            $row,
            ['proveedor', 'proveedor_nombre', 'suplidor'],
            $this->indicesFallback($usaFormatoPlantilla, 'proveedor', $this->resolverIndicesRelativos($bodegaIndiceDetectado, [6, 13, 12, 11]))
        );
        $fechaFabricacion = $this->normalizarFecha($this->obtenerValorCrudoFlexible(
            $row,
            ['fecha_fabricacion', 'fecha_fab', 'fecha_elaboracion'],
            $this->indicesFallback($usaFormatoPlantilla, 'fecha_fabricacion', $this->resolverIndicesRelativos($bodegaIndiceDetectado, [4, 11, 12, 13]))
        ));
        $fechaVencimiento = $this->normalizarFecha($this->obtenerValorCrudoFlexible(
            $row,
            ['fecha_vencimiento', 'fecha_vence', 'fecha_caducidad', 'fecha_expiracion'],
            $this->indicesFallback($usaFormatoPlantilla, 'fecha_vencimiento', $this->resolverIndicesRelativos($bodegaIndiceDetectado, [5, 12, 13, 14]))
        ));

        if ($stockInicial < 0) {
            throw new \RuntimeException('stock_inicial no puede ser negativo.');
        }

        if ($costoPromedio < 0) {
            throw new \RuntimeException('costo_promedio no puede ser negativo.');
        }

        $ingredienteActivo = $this->obtenerValorFlexible(
            $row,
            ['ingrediente_activo', 'ingrediente', 'ingredienteactivo', 'ingredientes_activo', 'componente_activo'],
            $this->indicesFallback($usaFormatoPlantilla, 'ingrediente_activo', [2])
        );

        $insumo = $this->resolverInsumo($codigo, $bodega->sucursal_id ?? $this->defaultSucursalId);

        $esNuevoInsumo = !$insumo->exists;

        $insumo->fill($this->filtrarColumnasPersistidas('insumos', array_merge([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'unidad_medida' => $unidad,
            'costo_estimado' => $costoPromedio,
            'stock_minimo' => $stockMinimo,
            'estado' => $estado,
            'sucursal_id' => $bodega->sucursal_id ?? $this->defaultSucursalId,
        ], $this->resolverPayloadIngrediente($ingredienteActivo), $this->resolverPayloadCategoria($categoria, $bodega))));

        if ($esNuevoInsumo) {
            $insumo->fill($this->filtrarColumnasPersistidas('insumos', [
                'created_by' => $this->userId,
            ]));
            $this->stats['insumos_creados']++;
        } else {
            $insumo->fill($this->filtrarColumnasPersistidas('insumos', [
                'updated_by' => $this->userId,
            ]));
            $this->stats['insumos_actualizados']++;
        }

        $this->limpiarAtributosNoPersistibles($insumo, 'insumos');
        $insumo->save();
        $this->guardarInsumoEnCache($insumo);

        $inventario = $this->resolverInventario($bodega, $insumo, $numeroLote, $costoPromedio, $fechaFabricacion, $fechaVencimiento);

        $stockAnterior = (float) $inventario->stock_actual;
        $nuevoStock = $stockAnterior + $stockInicial;

        $nuevoCostoPromedio = $stockInicial > 0
            ? ($stockAnterior <= 0
                ? $costoPromedio
                : (($inventario->costo_promedio * $stockAnterior) + ($costoPromedio * $stockInicial)) / $nuevoStock)
            : ($costoPromedio > 0 ? $costoPromedio : $inventario->costo_promedio);

        $this->actualizarInventarioPersistido($inventario, $this->filtrarColumnasPersistidas('inventario_bodegas', [
            'stock_actual' => $nuevoStock,
            'costo_promedio' => $nuevoCostoPromedio,
            'fecha_fabricacion' => $fechaFabricacion ?: $inventario->fecha_fabricacion,
            'fecha_vencimiento' => $fechaVencimiento ?: $inventario->fecha_vencimiento,
        ]));
        $this->guardarInventarioEnCache($inventario);

        $this->stats['lotes_creados_o_actualizados']++;

        if ($stockInicial > 0) {
            $movimiento = MovimientoInventario::create($this->filtrarColumnasPersistidas('movimiento_inventarios', [
                'empresa_id' => $bodega->empresa_id ?? $this->defaultEmpresaId,
                'insumo_id' => $insumo->id,
                'bodega_destino_id' => $bodega->id,
                'tipo' => 'ENTRADA',
                'cantidad' => $stockInicial,
                'precio_unitario' => $costoPromedio,
                'costo_unitario' => $nuevoCostoPromedio,
                'stock_anterior' => $stockAnterior,
                'stock_actual' => $nuevoStock,
                'descripcion' => $proveedor ?: 'Importacion inicial por Excel',
                'referencia' => 'IMPORTACION_EXCEL',
                'numero_lote' => $numeroLote,
                'fecha_fabricacion' => $fechaFabricacion,
                'fecha_vencimiento' => $fechaVencimiento,
                'sucursal_id' => $bodega->sucursal_id ?? $this->defaultSucursalId,
                'created_by' => $this->userId,
            ]));

            $facturaPayload = $this->filtrarColumnasPersistidas('factura_inventarios', [
                'empresa_id' => $bodega->empresa_id ?? $this->defaultEmpresaId,
                'movimiento_id' => $movimiento->id,
                'insumo_id' => $insumo->id,
                'bodega_id' => $bodega->id,
                'cantidad' => $stockInicial,
                'precio_unitario' => $costoPromedio,
                'total' => $stockInicial * $costoPromedio,
                'proveedor' => $proveedor,
                'numero_lote' => $numeroLote,
                'fecha_fabricacion' => $fechaFabricacion,
                'fecha_vencimiento' => $fechaVencimiento,
                'archivo' => null,
                'created_by' => $this->userId,
            ]);

            if ($facturaPayload !== []) {
                FacturaInventario::create($facturaPayload);
            }

            $this->entradaLegacySyncService->registrar([
                'insumo_id' => $insumo->id,
                'bodega_id' => $bodega->id,
                'tipo' => 'inventario_inicial',
                'cantidad' => $stockInicial,
                'costo_unitario' => $costoPromedio,
                'proveedor' => $proveedor,
                'fecha_ingreso' => now()->toDateString(),
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);

            $this->stats['movimientos_creados']++;
        }

        $this->stats['filas_importadas']++;
    }

    protected function resolverBodega(string $referencia): ?Bodega
    {
        $cacheKey = strtolower(trim($referencia));
        if (array_key_exists($cacheKey, $this->bodegaCache)) {
            return $this->bodegaCache[$cacheKey];
        }

        if (is_numeric($referencia)) {
            $bodega = Bodega::query()->find((int) $referencia);
            if ($bodega) {
                return $this->guardarBodegaEnCache($referencia, $bodega);
            }
        }

        $query = Bodega::query();

        if ($this->bodegasTieneCodigo === null) {
            $this->bodegasTieneCodigo = Schema::hasColumn('bodegas', 'codigo');
        }

        if ($this->bodegasTieneCodigo) {
            $bodegaPorCodigo = (clone $query)->where('codigo', $referencia)->first();
            if ($bodegaPorCodigo) {
                return $this->guardarBodegaEnCache($referencia, $bodegaPorCodigo);
            }
        }

        $bodegaPorNombre = (clone $query)->where('nombre', $referencia)->first();

        return $this->guardarBodegaEnCache($referencia, $bodegaPorNombre);
    }

    protected function resolverInsumo(string $codigo, ?int $sucursalId): Insumo
    {
        $cacheKey = $this->insumoCacheKey($codigo, $sucursalId);
        if (isset($this->insumoCache[$cacheKey])) {
            return $this->insumoCache[$cacheKey];
        }

        $atributosBusqueda = ['codigo' => $codigo];

        if ($this->tablaInsumosTieneSucursalId()) {
            $atributosBusqueda['sucursal_id'] = $sucursalId;
        }

        $insumo = Insumo::query()->firstOrNew($atributosBusqueda);

        $this->insumoCache[$cacheKey] = $insumo;

        return $insumo;
    }

    protected function resolverPayloadCategoria(string $categoriaNombre, Bodega $bodega): array
    {
        $categoriaNombre = trim($categoriaNombre);

        if ($categoriaNombre === '') {
            return [];
        }

        if ($this->categoriaEsInvalida($categoriaNombre)) {
            throw new \RuntimeException('La categoria "' . $categoriaNombre . '" no es valida para importar. Revisa el archivo: parece una unidad u otro dato corrido de columna.');
        }

        $payload = [];

        if ($this->tablaTieneColumna('insumos', 'categoria_nombre')) {
            $payload['categoria_nombre'] = $categoriaNombre;
        }

        if ($this->tablaTieneColumna('insumos', 'categoria_id')) {
            $payload['categoria_id'] = $this->resolverOCrearCategoriaId($categoriaNombre);
        }

        return $payload;
    }

    protected function resolverPayloadIngrediente(?string $ingredienteActivo): array
    {
        $ingredienteActivo = trim((string) ($ingredienteActivo ?? ''));

        if ($ingredienteActivo === '') {
            return [];
        }

        $payload = [];

        if ($this->tablaTieneColumna('insumos', 'ingrediente_activo')) {
            $payload['ingrediente_activo'] = $ingredienteActivo;
        }

        if ($this->tablaTieneColumna('insumos', 'ingredientes_activo')) {
            $payload['ingredientes_activo'] = $ingredienteActivo;
        }

        return $payload;
    }

    protected function resolverOCrearCategoriaId(string $nombre): int
    {
        $nombre = $this->normalizarNombreCategoria($nombre);
        $cacheKey = strtolower($nombre);

        if ($this->categoriaEsInvalida($nombre)) {
            throw new \RuntimeException('No se permite crear la categoria "' . $nombre . '" porque parece una unidad u otro valor invalido.');
        }

        if (isset($this->categoriaCache[$cacheKey])) {
            return (int) $this->categoriaCache[$cacheKey];
        }

        $categoriaId = $this->buscarCategoriaIdPorNombre($nombre);

        if (! $categoriaId) {
            $payload = $this->filtrarColumnasPersistidas('categorias', [
                'nombre' => $nombre,
                'usuarios_id' => $this->userId,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);

            $categoriaId = DB::table('categorias')->insertGetId($payload);
        }

        $this->categoriaCache[$cacheKey] = (int) $categoriaId;

        return (int) $categoriaId;
    }

    protected function buscarCategoriaIdPorNombre(string $nombre): ?int
    {
        $nombreNormalizado = $this->normalizarCategoriaParaComparacion($nombre);

        $categoria = DB::table('categorias')
            ->select(['id', 'nombre'])
            ->get()
            ->first(function ($categoria) use ($nombreNormalizado) {
                return $this->normalizarCategoriaParaComparacion((string) $categoria->nombre) === $nombreNormalizado;
            });

        return $categoria?->id ? (int) $categoria->id : null;
    }

    protected function normalizarNombreCategoria(string $nombre): string
    {
        $nombre = trim($nombre);
        $nombre = preg_replace('/\s+/', ' ', $nombre) ?? $nombre;

        return $nombre;
    }

    protected function normalizarCategoriaParaComparacion(string $nombre): string
    {
        $nombre = $this->normalizarNombreCategoria($nombre);

        return str_replace('_', '', $this->normalizarClave($nombre));
    }

    protected function categoriaEsInvalida(string $nombre): bool
    {
        $normalizada = strtoupper(trim($nombre));

        if ($normalizada === '') {
            return true;
        }

        $categoriasInvalidas = [
            'C/U', 'CU', 'G', 'KG', 'KGS', 'L', 'LT', 'LTS', 'ML', 'M', 'GR', 'UND', 'UNIDAD', 'UNIDADES',
        ];

        if (in_array($normalizada, $categoriasInvalidas, true)) {
            return true;
        }

        return preg_match('/^(=|\+|-|\*|\/)/', $normalizada) === 1;
    }

    protected function guardarInsumoEnCache(Insumo $insumo): void
    {
        $sucursalId = $this->tablaInsumosTieneSucursalId() ? $insumo->sucursal_id : null;
        $this->insumoCache[$this->insumoCacheKey((string) $insumo->codigo, $sucursalId)] = $insumo;
    }

    protected function resolverInventario(Bodega $bodega, Insumo $insumo, ?string $numeroLote, float $costoPromedio, ?string $fechaFabricacion, ?string $fechaVencimiento): InventarioBodega
    {
        $numeroLotePersistible = $this->tablaTieneColumna('inventario_bodegas', 'numero_lote') ? $numeroLote : null;

        $cacheKey = $this->inventarioCacheKey(
            $bodega->empresa_id ?? $this->defaultEmpresaId,
            $insumo->id,
            $bodega->id,
            $numeroLotePersistible
        );

        if (isset($this->inventarioCache[$cacheKey])) {
            return $this->inventarioCache[$cacheKey];
        }

        $atributosBusqueda = $this->filtrarColumnasPersistidas('inventario_bodegas', [
            'empresa_id' => $bodega->empresa_id ?? $this->defaultEmpresaId,
            'insumo_id' => $insumo->id,
            'bodega_id' => $bodega->id,
            'numero_lote' => $numeroLotePersistible,
        ]);

        $atributosCreacion = $this->filtrarColumnasPersistidas('inventario_bodegas', [
            'stock_actual' => 0,
            'costo_promedio' => $costoPromedio,
            'fecha_fabricacion' => $fechaFabricacion,
            'fecha_vencimiento' => $fechaVencimiento,
        ]);

        $inventario = InventarioBodega::firstOrCreate($atributosBusqueda, $atributosCreacion);

        $this->inventarioCache[$cacheKey] = $inventario;

        return $inventario;
    }

    protected function guardarInventarioEnCache(InventarioBodega $inventario): void
    {
        $this->inventarioCache[$this->inventarioCacheKey(
            $inventario->empresa_id,
            $inventario->insumo_id,
            $inventario->bodega_id,
            $inventario->numero_lote
        )] = $inventario;
    }

    protected function actualizarInventarioPersistido(InventarioBodega $inventario, array $payload): void
    {
        if ($payload === []) {
            return;
        }

        if ($this->tablaTieneColumna('inventario_bodegas', 'updated_at') && ! array_key_exists('updated_at', $payload)) {
            $payload['updated_at'] = now();
        }

        $query = InventarioBodega::query()
            ->where('insumo_id', $inventario->insumo_id)
            ->where('bodega_id', $inventario->bodega_id);

        if ($this->tablaTieneColumna('inventario_bodegas', 'empresa_id') && isset($inventario->empresa_id)) {
            $query->where('empresa_id', $inventario->empresa_id);
        }

        if ($this->tablaTieneColumna('inventario_bodegas', 'numero_lote')) {
            if ($inventario->numero_lote === null) {
                $query->whereNull('numero_lote');
            } else {
                $query->where('numero_lote', $inventario->numero_lote);
            }
        }

        $query->update($payload);
        $inventario->forceFill($payload);
    }

    protected function guardarBodegaEnCache(string $referencia, ?Bodega $bodega): ?Bodega
    {
        $cacheKey = strtolower(trim($referencia));
        $this->bodegaCache[$cacheKey] = $bodega;

        if ($bodega) {
            $this->bodegaCache[(string) $bodega->id] = $bodega;

            if ($this->bodegasTieneCodigo && ! empty($bodega->codigo)) {
                $this->bodegaCache[strtolower(trim((string) $bodega->codigo))] = $bodega;
            }

            $this->bodegaCache[strtolower(trim((string) $bodega->nombre))] = $bodega;
        }

        return $bodega;
    }

    protected function insumoCacheKey(string $codigo, ?int $sucursalId): string
    {
        return strtolower(trim($codigo)) . '|' . ($sucursalId ?? 0);
    }

    protected function inventarioCacheKey(?int $empresaId, ?int $insumoId, ?int $bodegaId, mixed $numeroLote): string
    {
        return implode('|', [
            $empresaId ?? 0,
            $insumoId ?? 0,
            $bodegaId ?? 0,
            strtolower(trim((string) ($numeroLote ?? ''))),
        ]);
    }

    protected function limpiarTexto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);
        return $texto === '' ? null : $texto;
    }

    protected function normalizarFila(Collection $row): Collection
    {
        $normalizada = [];

        foreach ($row as $clave => $valor) {
            $claveNormalizada = $this->resolverClaveFila($clave);
            $normalizada[$claveNormalizada] = $valor;
        }

        return collect($normalizada);
    }

    protected function expandirFilaSeparada(mixed $row): Collection
    {
        $row = $row instanceof Collection ? $row->values() : collect((array) $row)->values();

        if ($row->count() !== 1) {
            $filaReconstruida = $this->reconstruirFilaSeparada($row);
            if ($filaReconstruida !== null) {
                return $filaReconstruida;
            }

            return $row;
        }

        $valor = $row->first();
        if (! is_string($valor)) {
            return $row;
        }

        $texto = trim($this->removerBom($valor));
        if ($texto === '') {
            return collect();
        }

        $delimitador = $this->detectarDelimitadorFila($texto);
        if ($delimitador === null) {
            return $row;
        }

        $partes = str_getcsv($texto, $delimitador);
        if (count($partes) <= 1) {
            return $row;
        }

        return collect($partes)->map(function ($item) {
            return is_string($item) ? trim($this->removerBom($item)) : $item;
        })->values();
    }

    protected function reconstruirFilaSeparada(Collection $row): ?Collection
    {
        $texto = $row
            ->map(fn ($item) => is_string($item) ? trim($this->removerBom($item)) : $item)
            ->filter(fn ($item) => $item !== null && $item !== '')
            ->implode(' ');

        if ($texto === '') {
            return null;
        }

        $delimitador = $this->detectarDelimitadorFila($texto);
        if ($delimitador === null) {
            return null;
        }

        $celdasConDelimitador = $row->filter(function ($item) use ($delimitador) {
            return is_string($item) && str_contains($item, $delimitador);
        })->count();

        if ($celdasConDelimitador < 2) {
            return null;
        }

        $partes = str_getcsv($texto, $delimitador);
        if (count($partes) <= $row->count()) {
            return null;
        }

        return collect($partes)->map(function ($item) {
            return is_string($item) ? trim($this->removerBom($item)) : $item;
        })->values();
    }

    protected function detectarDelimitadorFila(string $texto): ?string
    {
        $candidatos = [',', ';', "\t"];
        $mejorDelimitador = null;
        $mejorConteo = 0;

        foreach ($candidatos as $delimitador) {
            $conteo = substr_count($texto, $delimitador);
            if ($conteo > $mejorConteo) {
                $mejorConteo = $conteo;
                $mejorDelimitador = $delimitador;
            }
        }

        return $mejorConteo > 0 ? $mejorDelimitador : null;
    }

    protected function resolverMapaCabeceras(array $fila): ?array
    {
        $mapa = [];

        foreach (array_values($fila) as $indice => $valor) {
            $clave = $this->normalizarClave($valor);
            if ($clave === '') {
                continue;
            }

            $mapa[$indice] = $clave;
        }

        if ($mapa === []) {
            return null;
        }

        $claves = array_values($mapa);
        $pareceCabecera = in_array('codigo', $claves, true)
            && in_array('nombre', $claves, true)
            && collect($claves)->contains(fn (string $clave) => $this->esAliasBodega($clave));

        return $pareceCabecera ? $mapa : null;
    }

    protected function esFilaCabecera(Collection $row): bool
    {
        $valores = $row->values()
            ->map(fn ($valor) => $this->normalizarClave($valor))
            ->filter(fn (?string $valor) => $valor !== null && $valor !== '')
            ->values();

        if ($valores->isEmpty()) {
            return false;
        }

        $coincidencias = 0;
        $cabecerasEsperadas = [
            'codigo',
            'nombre',
            'ingrediente_activo',
            'categoria_nombre',
            'unidad_medida',
            'stock_minimo',
            'estado',
            'bodega_id',
            'bodega_destino',
            'almacen_destino',
            'numero_lote',
            'stock_inicial',
            'costo_promedio',
            'precio',
            'fecha_fabricacion',
            'fecha_vencimiento',
            'proveedor',
        ];

        foreach ($cabecerasEsperadas as $cabecera) {
            if ($valores->contains($this->normalizarClave($cabecera))) {
                $coincidencias++;
            }
        }

        return $coincidencias >= 4
            && $valores->contains('codigo')
            && $valores->contains('nombre')
            && ($valores->contains('stock_inicial') || $valores->contains('costo_promedio') || $valores->contains(fn (string $valor) => $this->esAliasBodega($valor)));
    }

    protected function resolverClaveFila(mixed $clave): string
    {
        if (is_numeric($clave)) {
            $indice = (int) $clave;
            if ($this->headerMap !== null && array_key_exists($indice, $this->headerMap)) {
                return $this->headerMap[$indice];
            }

            return (string) $indice;
        }

        return $this->normalizarClave($clave);
    }

    protected function resolverReferenciaBodega(Collection $row): array
    {
        $aliases = $this->obtenerAliasesBodega();

        foreach ($aliases as $alias) {
            $clave = $this->normalizarClave($alias);
            if (! $row->has($clave)) {
                continue;
            }

            $valor = $this->limpiarTexto($row->get($clave));
            if ($valor === null) {
                continue;
            }

            return [$valor, $clave, null];
        }

        $valorPlantilla = $this->obtenerValorPorIndicePlantilla($row, 'bodega_id');
        if ($valorPlantilla !== null && $this->resolverBodega($valorPlantilla)) {
            return [$valorPlantilla, 'indice_plantilla_7', self::PLANTILLA_INDICES['bodega_id']];
        }

        $indicesCandidatos = $this->inferredBodegaIndex !== null
            ? array_values(array_unique(array_merge([$this->inferredBodegaIndex], range(max(0, $this->inferredBodegaIndex - 2), $this->inferredBodegaIndex + 2))))
            : range(7, 15);

        foreach ($indicesCandidatos as $indice) {
            $clave = (string) $indice;
            if (! $row->has($clave)) {
                continue;
            }

            $valor = $this->limpiarTexto($row->get($clave));
            if ($valor === null) {
                continue;
            }

            if ($this->resolverBodega($valor)) {
                return [$valor, 'indice_' . $indice, $indice];
            }
        }

        $fallbackIndices = $this->inferredBodegaIndex !== null ? [$this->inferredBodegaIndex, 7] : [7];
        $valor = $this->obtenerValorFlexible($row, $aliases, $fallbackIndices);

        return [$valor, null, null];
    }

    protected function obtenerAliasesBodega(): array
    {
        return [
            'bodega_id', 'bodega', 'bodega_codigo', 'bodega_nombre',
            'bodega_destino', 'bodega_destino_id', 'bodega_destino_codigo', 'bodega_destino_nombre',
            'almacen', 'almacen_id', 'almacen_codigo', 'almacen_nombre',
            'almacen_destino', 'almacen_destino_id', 'almacen_destino_codigo', 'almacen_destino_nombre',
            'destino_bodega', 'destino_almacen',
        ];
    }

    protected function esAliasBodega(string $clave): bool
    {
        return in_array($clave, array_map(fn (string $alias) => $this->normalizarClave($alias), $this->obtenerAliasesBodega()), true);
    }

    protected function resolverIndicesRelativos(?int $indiceBase, array $indicesPorDefecto): array
    {
        if ($indiceBase === null) {
            if ($this->inferredBodegaIndex !== null && ! empty($indicesPorDefecto)) {
                $baseDefecto = $indicesPorDefecto[0] - 2;
                $indices = [];
                foreach ($indicesPorDefecto as $indice) {
                    $indices[] = $this->inferredBodegaIndex + ($indice - $baseDefecto);
                }

                return array_values(array_unique(array_merge($indices, $indicesPorDefecto)));
            }

            return $indicesPorDefecto;
        }

        $indices = [];
        foreach ($indicesPorDefecto as $posicion => $indice) {
            if ($posicion === 0) {
                $indices[] = $indiceBase + $indice;
                continue;
            }

            $indices[] = $indice;
        }

        return array_values(array_unique($indices));
    }

    protected function filaCoincideConPlantilla(Collection $row): bool
    {
        $codigo = $this->obtenerValorPorIndicePlantilla($row, 'codigo');
        $nombre = $this->obtenerValorPorIndicePlantilla($row, 'nombre');
        $bodega = $this->obtenerValorPorIndicePlantilla($row, 'bodega_id');

        return $codigo !== null
            && $nombre !== null
            && $bodega !== null
            && $this->resolverBodega($bodega) !== null;
    }

    protected function indicesFallback(bool $usaFormatoPlantilla, string $campoPlantilla, array $fallback): array
    {
        if ($usaFormatoPlantilla && array_key_exists($campoPlantilla, self::PLANTILLA_INDICES)) {
            return [self::PLANTILLA_INDICES[$campoPlantilla]];
        }

        return $fallback;
    }

    protected function obtenerValorPorIndicePlantilla(Collection $row, string $campo): ?string
    {
        if (! array_key_exists($campo, self::PLANTILLA_INDICES)) {
            return null;
        }

        $valores = $row->values();
        $indice = self::PLANTILLA_INDICES[$campo];

        if (! $valores->has($indice)) {
            return null;
        }

        return $this->limpiarTexto($valores->get($indice));
    }

    protected function normalizarClave(mixed $clave): string
    {
        $texto = strtolower(trim($this->removerBom((string) $clave)));
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        // Reemplaza todos los espacios y caracteres no alfanuméricos por guion bajo
        $texto = preg_replace('/[^a-z0-9]+/', '_', $texto) ?? $texto;
        // Alias especial para aceptar exactamente 'Unidad medida base' como 'unidad_medida'
        if ($texto === 'unidad_medida_base' || $texto === 'unidad_medida__base') {
            return 'unidad_medida';
        }
        if (in_array($texto, ['costo_promedio', 'costo_prom', 'costo_prome', 'costo_promed', 'precio_promedio'], true)) {
            return 'costo_promedio';
        }
        if ($texto === 'unidad_medida') {
            return 'unidad_medida';
        }
        return trim($texto, '_');
    }

    protected function removerBom(string $texto): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $texto) ?? $texto;
    }

    protected function filtrarColumnasPersistidas(string $tabla, array $payload): array
    {
        if (! isset($this->columnasTablaCache[$tabla])) {
            $this->columnasTablaCache[$tabla] = array_flip(Schema::getColumnListing($tabla));
        }

        return array_intersect_key($payload, $this->columnasTablaCache[$tabla]);
    }

    protected function limpiarAtributosNoPersistibles(Model $modelo, string $tabla): void
    {
        $modelo->timestamps = $this->tablaTieneColumna($tabla, 'created_at') && $this->tablaTieneColumna($tabla, 'updated_at');
        $modelo->setRawAttributes(
            $this->filtrarColumnasPersistidas($tabla, $modelo->getAttributes()),
            false
        );
    }

    protected function obtenerValor(Collection $row, array $aliases): ?string
    {
        $valor = $this->obtenerValorCrudo($row, $aliases);
        return $this->limpiarTexto($valor);
    }

    protected function obtenerValorFlexible(Collection $row, array $aliases, array $indices = []): ?string
    {
        $valor = $this->obtenerValorCrudoFlexible($row, $aliases, $indices);
        return $this->limpiarTexto($valor);
    }

    protected function obtenerValorCrudo(Collection $row, array $aliases)
    {
        foreach ($aliases as $alias) {
            $clave = $this->normalizarClave($alias);
            if (! $row->has($clave)) {
                continue;
            }

            $valor = $row->get($clave);
            if ($valor !== null && trim((string) $valor) !== '') {
                return $valor;
            }
        }

        return null;
    }

    protected function obtenerValorCrudoFlexible(Collection $row, array $aliases, array $indices = [])
    {
        $valor = $this->obtenerValorCrudo($row, $aliases);
        if ($valor !== null && trim((string) $valor) !== '') {
            return $valor;
        }

        $valores = $row->values();
        foreach ($indices as $indice) {
            if (! $valores->has($indice)) {
                continue;
            }

            $valor = $valores->get($indice);
            if ($valor !== null && trim((string) $valor) !== '') {
                return $valor;
            }
        }

        return null;
    }

    protected function toFloat(mixed $valor): float
    {
        if ($valor === null || $valor === '') {
            return 0;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        return (float) str_replace([' ', ','], ['', '.'], (string) $valor);
    }

    protected function toBool(mixed $valor, bool $default = true): bool
    {
        if ($valor === null || $valor === '') {
            return $default;
        }

        return in_array(strtolower(trim((string) $valor)), ['1', 'true', 'activo', 'activa', 'si', 'sí'], true);
    }

    protected function normalizarFecha(mixed $valor): ?string
    {
        if (is_numeric($valor)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $valor)->format('Y-m-d');
            } catch (\Throwable $error) {
                return null;
            }
        }

        $texto = $this->limpiarTexto($valor);

        if (!$texto) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($texto)->format('Y-m-d');
        } catch (\Throwable $error) {
            return null;
        }
    }

    protected function registrarError(int $fila, string $mensaje): void
    {
        $this->stats['filas_error']++;
        $this->errores[] = 'Fila ' . $fila . ': ' . $mensaje;
    }

    protected function tablaInsumosTieneSucursalId(): bool
    {
        if ($this->insumosTieneSucursalId === null) {
            $this->insumosTieneSucursalId = Schema::hasColumn('insumos', 'sucursal_id');
        }

        return $this->insumosTieneSucursalId;
    }

    protected function tablaTieneColumna(string $tabla, string $columna): bool
    {
        return Schema::hasColumn($tabla, $columna);
    }

    protected function inferirIndiceBodega(Collection $rows): ?int
    {
        $puntajes = [];

        foreach ($rows->take(150) as $row) {
            $fila = $this->expandirFilaSeparada($row)->values();

            foreach ($fila as $indice => $valor) {
                $texto = $this->limpiarTexto($valor);
                if ($texto === null) {
                    continue;
                }

                if ($this->resolverBodega($texto)) {
                    $puntajes[$indice] = ($puntajes[$indice] ?? 0) + 1;
                }
            }
        }

        if ($puntajes === []) {
            return null;
        }

        arsort($puntajes);
        $mejorIndice = array_key_first($puntajes);
        $mejorPuntaje = $puntajes[$mejorIndice] ?? 0;

        return $mejorPuntaje >= 3 ? (int) $mejorIndice : null;
    }

    public function chunkSize(): int
    {
        return 250;
    }

    protected function esPrimerChunk(): bool
    {
        return $this->getCurrentChunkOffset() <= 1;
    }

    protected function getCurrentChunkOffset(): int
    {
        return (int) ($this->getChunkOffset() ?? 0);
    }
}