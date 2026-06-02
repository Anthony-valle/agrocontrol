<?php

namespace App\Imports;

use App\Models\Bodega;
use App\Models\Categorias;
use App\Models\Consumo;
use App\Models\Consumo_detalles;
use App\Models\Cultivo;
use App\Models\InventarioBodega;
use App\Models\Insumo;
use App\Models\User;
use App\Services\InventarioService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ConsumosImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithCalculatedFormulas
{
    private int $userId;

    private InventarioService $inventarioService;

    private ?User $user;

    private array $stats = [
        'filas_procesadas' => 0,
        'filas_error' => 0,
        'filas_negativas_ajustadas' => 0,
        'consumos_creados' => 0,
        'detalles_creados' => 0,
        'consumos_reales_aplicados' => 0,
        'consumos_historicos_sin_descuento' => 0,
        'filas_cantidad_directa' => 0,
    ];

    private array $errores = [];

    private array $columnasPorTabla = [];

    private array $categoriasPorId = [];

    public function __construct(int $userId, ?InventarioService $inventarioService = null)
    {
        $this->userId = $userId;
        $this->inventarioService = $inventarioService ?? app(InventarioService::class);
        $this->user = User::query()->with('bodegaConsumo', 'rol')->find($userId);
    }

    public function collection(Collection $rows): void
    {
        $grupos = [];

        foreach ($rows as $index => $row) {
            $this->stats['filas_procesadas']++;
            $fila = $index + 2;

            try {
                $referencia = $this->limpiarTexto($this->obtenerValor($row, ['consumo_referencia', 'referencia_consumo', 'codigo_consumo']));
                if ($referencia === '') {
                    throw new \RuntimeException('consumo_referencia es obligatoria para agrupar las filas del mismo consumo.');
                }

                $cultivo = $this->resolverCultivo(
                    $this->obtenerValor($row, ['cultivo_id']),
                    $this->obtenerValor($row, ['cultivo_codigo', 'codigo_cultivo']),
                    $this->obtenerValor($row, ['cultivo_nombre', 'nombre_cultivo', 'cultivo'])
                );

                if (! $cultivo) {
                    throw new \RuntimeException('No se encontro el cultivo. Envia cultivo_id, cultivo_codigo o cultivo_nombre valido.');
                }

                $fechaConsumo = $this->normalizarFecha($this->obtenerValor($row, ['fecha_consumo', 'consumo_fecha']));
                if (! $fechaConsumo) {
                    throw new \RuntimeException('fecha_consumo es obligatoria y debe ser valida.');
                }

                $aplicarConsumoReal = $this->toBoolean($this->obtenerValor($row, ['aplicar_consumo_real_bodega', 'hacer_consumo_real_bodega', 'consumo_real_bodega']), false);
                $descripcion = $this->limpiarTexto($this->obtenerValor($row, ['descripcion_consumo', 'descripcion']));
                $cantidadOriginal = round($this->toFloat($this->obtenerValor($row, ['cantidad', 'cantidad_consumo', 'cantidad_por_ha', 'cantidad_ha', 'cantidad_por_hectarea'])), 3);
                $precioHistoricoOriginal = round($this->toFloat($this->obtenerValor($row, ['precio_unitario', 'costo_unitario', 'costo_unitario_consumo', 'precio_consumo', 'costo'])), 3);
                $subtotalEnviadoRaw = $this->obtenerValor($row, ['subtotal', 'total_linea', 'subtotal_consumo']);
                $subtotalOriginal = round($this->toFloat($subtotalEnviadoRaw), 3);

                $signoMovimiento = $this->resolverSignoMovimiento($cantidadOriginal, $precioHistoricoOriginal, $subtotalEnviadoRaw, $subtotalOriginal);
                $cantidadBase = round(abs($cantidadOriginal), 3);
                $precioHistoricoEnviado = round(abs($precioHistoricoOriginal), 3);
                $subtotalEnviado = round($subtotalOriginal, 3);

                if ($signoMovimiento < 0) {
                    $this->stats['filas_negativas_ajustadas']++;
                }

                if ($cantidadBase <= 0) {
                    throw new \RuntimeException('La cantidad del consumo debe ser mayor a 0 en valor absoluto.');
                }

                $this->stats['filas_cantidad_directa']++;

                $insumoCodigo = $this->limpiarTexto($this->obtenerValor($row, ['insumo_codigo', 'codigo_insumo']));
                if ($insumoCodigo === '') {
                    throw new \RuntimeException('insumo_codigo es obligatorio para resolver automaticamente el insumo, categoria, unidad y costo.');
                }

                $insumo = $this->resolverInsumoPorCodigo($insumoCodigo);

                if (! $insumo) {
                    throw new \RuntimeException('No se encontro un insumo activo para insumo_codigo=' . $insumoCodigo . '.');
                }

                if ($descripcion === '') {
                    $descripcion = $insumo->nombre ?? '';
                }

                $categoria = $this->resolverCategoriaInsumo($insumo);

                if ($descripcion === '') {
                    throw new \RuntimeException('descripcion_consumo es obligatoria cuando no se puede resolver desde el insumo.');
                }

                $unidad = trim((string) ($insumo->unidad_medida ?? ''));

                if ($unidad === '') {
                    throw new \RuntimeException('El insumo con codigo ' . $insumoCodigo . ' no tiene unidad_medida configurada.');
                }

                $unidadEnviada = mb_strtoupper($this->limpiarTexto($this->obtenerValor($row, ['unidad_medida', 'unidad', 'u_m'])));
                if ($unidadEnviada !== '' && $unidadEnviada !== mb_strtoupper($unidad)) {
                    throw new \RuntimeException('La unidad_medida enviada (' . $unidadEnviada . ') no coincide con la configurada para el insumo (' . $unidad . ').');
                }

                $bodega = $this->resolverBodega(
                    $this->obtenerValor($row, ['bodega_id', 'almacen_id']),
                    $this->obtenerValor($row, ['bodega_nombre', 'bodega', 'almacen', 'almacen_nombre'])
                );

                $lote = $this->normalizarLote($this->obtenerValor($row, ['lote_consumo', 'numero_lote', 'lote']));

                $costoUnitario = $precioHistoricoEnviado;

                if (! $aplicarConsumoReal && $precioHistoricoEnviado <= 0) {
                    throw new \RuntimeException('Cuando aplicar_consumo_real_bodega = NO debes enviar precio_unitario valido para respetar el costo historico notificado en ese momento.');
                }

                if ($costoUnitario <= 0) {
                    $costoUnitario = $this->resolverCostoAutomatico($insumo, $bodega?->id, $lote, $aplicarConsumoReal);
                }
                if ($costoUnitario <= 0) {
                    throw new \RuntimeException('Debes enviar precio_unitario valido o tener un costo automatico disponible para insumo_codigo=' . $insumoCodigo . '.');
                }

                $cantidad = round($cantidadBase * $signoMovimiento, 3);
                $subtotalCalculado = round($cantidad * $costoUnitario, 3);
                $traeSubtotal = trim((string) $subtotalEnviadoRaw) !== '';

                if ($traeSubtotal && abs($subtotalEnviado - $subtotalCalculado) > 0.001) {
                    throw new \RuntimeException('El subtotal enviado no coincide con cantidad x precio_unitario. Esperado: ' . number_format($subtotalCalculado, 3, '.', '') . '.');
                }

                if (! $aplicarConsumoReal) {
                    $bodega = null;
                    $lote = null;
                }

                $this->ensureWarehouseAllowedForUser($aplicarConsumoReal, $bodega);

                if ($aplicarConsumoReal && ! $bodega) {
                    throw new \RuntimeException('Para aplicar consumo real de bodega debes enviar bodega_id o bodega_nombre valido.');
                }

                if (! isset($grupos[$referencia])) {
                    $grupos[$referencia] = [
                        'referencia' => $referencia,
                        'cultivo' => $cultivo,
                        'fecha_consumo' => $fechaConsumo,
                        'aplicar_consumo_real_bodega' => $aplicarConsumoReal,
                        'items' => [],
                    ];
                } else {
                    $grupo = $grupos[$referencia];

                    if ((int) $grupo['cultivo']->id !== (int) $cultivo->id) {
                        throw new \RuntimeException('Todas las filas con la misma consumo_referencia deben pertenecer al mismo cultivo.');
                    }

                    if ($grupo['fecha_consumo']->toDateString() !== $fechaConsumo->toDateString()) {
                        throw new \RuntimeException('Todas las filas con la misma consumo_referencia deben compartir la misma fecha_consumo.');
                    }

                    if ((bool) $grupo['aplicar_consumo_real_bodega'] !== (bool) $aplicarConsumoReal) {
                        throw new \RuntimeException('Todas las filas con la misma consumo_referencia deben usar el mismo valor en aplicar_consumo_real_bodega.');
                    }
                }

                $grupos[$referencia]['items'][] = [
                    'id' => $insumo?->id,
                    'nombre' => $descripcion,
                    'categoria' => $categoria,
                    'cantidad' => $cantidad,
                    'precio' => $costoUnitario,
                    'subtotal' => $subtotalCalculado,
                    'unidad' => $unidad,
                    'bodega_id' => $bodega?->id,
                    'lote' => $lote,
                ];
            } catch (\Throwable $error) {
                $this->stats['filas_error']++;
                $this->errores[] = 'Fila ' . $fila . ': ' . $error->getMessage();
            }
        }

        foreach ($grupos as $grupo) {
            DB::transaction(function () use ($grupo) {
                $total = round(collect($grupo['items'])->sum(fn (array $item) => $item['subtotal']), 3);

                $consumo = Consumo::create($this->filtrarColumnasPersistidas('consumos', [
                    'empresa_id' => $grupo['cultivo']->empresa_id,
                    'cultivo_id' => $grupo['cultivo']->id,
                    'fecha_consumo' => $grupo['fecha_consumo']->toDateString(),
                    'total' => $total,
                    'estado' => 'FINALIZADO',
                    'created_by' => $this->userId,
                    'validated_by' => $this->userId,
                ]));

                foreach ($grupo['items'] as $item) {
                    Consumo_detalles::create($this->filtrarColumnasPersistidas('consumo_detalles', [
                        'consumo_id' => $consumo->id,
                        'insumo_id' => $item['id'],
                        'categoria' => $item['categoria'],
                        'descripcion' => $item['nombre'],
                        'cantidad' => $item['cantidad'],
                        'unidad_medida' => $item['unidad'],
                        'costo_unitario' => $item['precio'],
                        'subtotal' => $item['subtotal'],
                        'bodega_id' => $item['bodega_id'],
                        'lote' => $item['lote'],
                        'created_by' => $this->userId,
                    ]));

                    $this->stats['detalles_creados']++;
                }

                if ($grupo['aplicar_consumo_real_bodega']) {
                    $this->inventarioService->registrarConsumo([
                        'consumo_id' => $consumo->id,
                        'cultivo_id' => $grupo['cultivo']->id,
                        'items' => $grupo['items'],
                    ]);
                    $this->stats['consumos_reales_aplicados']++;
                } else {
                    $this->stats['consumos_historicos_sin_descuento']++;
                }

                $this->stats['consumos_creados']++;
            });
        }
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getErrores(): array
    {
        return array_slice($this->errores, 0, 20);
    }

    public function getSummaryLines(): array
    {
        return [
            'Filas procesadas: ' . $this->stats['filas_procesadas'],
            'Filas negativas aplicadas como ajuste/resta: ' . $this->stats['filas_negativas_ajustadas'],
            'Consumos creados: ' . $this->stats['consumos_creados'],
            'Detalles creados: ' . $this->stats['detalles_creados'],
            'Consumos reales aplicados a bodega: ' . $this->stats['consumos_reales_aplicados'],
            'Consumos historicos sin descuento: ' . $this->stats['consumos_historicos_sin_descuento'],
            'Filas tomadas con cantidad directa del archivo: ' . $this->stats['filas_cantidad_directa'],
            'Filas con error: ' . $this->stats['filas_error'],
        ];
    }

    private function resolverSignoMovimiento(float $cantidad, float $precioUnitario, mixed $subtotalRaw, float $subtotal): int
    {
        if ($cantidad < 0 || $precioUnitario < 0) {
            return -1;
        }

        if ($subtotalRaw !== null && trim((string) $subtotalRaw) !== '' && $subtotal < 0) {
            return -1;
        }

        return 1;
    }

    private function resolverCultivo(mixed $cultivoId, mixed $cultivoCodigo, mixed $cultivoNombre): ?Cultivo
    {
        if ($cultivoId !== null && trim((string) $cultivoId) !== '' && is_numeric($cultivoId)) {
            $cultivo = Cultivo::query()->find((int) $cultivoId);
            if ($cultivo) {
                return $cultivo;
            }
        }

        $codigo = $this->limpiarTexto($cultivoCodigo);
        if ($codigo !== '') {
            $cultivo = Cultivo::query()->where('codigo', $codigo)->first();
            if ($cultivo) {
                return $cultivo;
            }
        }

        $nombre = $this->limpiarTexto($cultivoNombre);
        if ($nombre !== '') {
            return Cultivo::query()->where('nombre', $nombre)->first();
        }

        return null;
    }

    private function resolverInsumoPorCodigo(string $insumoCodigo): ?Insumo
    {
        return Insumo::query()->activos()->where('codigo', trim($insumoCodigo))->first();
    }

    private function resolverCostoAutomatico(Insumo $insumo, ?int $bodegaId, ?string $lote, bool $aplicarConsumoReal): float
    {
        if ($aplicarConsumoReal && $bodegaId) {
            $inventarioQuery = InventarioBodega::query()
                ->where('insumo_id', $insumo->id)
                ->where('bodega_id', $bodegaId);

            if (Schema::hasColumn('inventario_bodegas', 'numero_lote') && $lote !== null && $lote !== '') {
                $inventarioQuery->where('numero_lote', $lote);
            }

            $inventario = $inventarioQuery->first();
            $costoInventario = round((float) ($inventario?->costo_promedio ?? 0), 3);
            if ($costoInventario > 0) {
                return $costoInventario;
            }
        }

        return round((float) ($insumo->costo_estimado ?? 0), 3);
    }

    private function resolverCategoriaInsumo(Insumo $insumo): string
    {
        $categoriaNombre = trim((string) ($insumo->categoria_nombre ?? ''));
        if ($categoriaNombre !== '') {
            return $categoriaNombre;
        }

        $categoriaId = (int) ($insumo->categoria_id ?? 0);
        if ($categoriaId > 0) {
            if (! array_key_exists($categoriaId, $this->categoriasPorId)) {
                $this->categoriasPorId[$categoriaId] = (string) (Categorias::query()->find($categoriaId)?->nombre ?? '');
            }

            $categoriaNombre = trim($this->categoriasPorId[$categoriaId]);
            if ($categoriaNombre !== '') {
                return $categoriaNombre;
            }
        }

        return 'Otros Insumos';
    }

    private function resolverBodega(mixed $bodegaId, mixed $bodegaNombre): ?Bodega
    {
        if ($bodegaId !== null && trim((string) $bodegaId) !== '' && is_numeric($bodegaId)) {
            $bodega = Bodega::query()->find((int) $bodegaId);
            if ($bodega) {
                return $bodega;
            }
        }

        $nombre = $this->limpiarTexto($bodegaNombre);
        if ($nombre !== '') {
            return Bodega::query()->where('nombre', $nombre)->first();
        }

        return null;
    }

    private function ensureWarehouseAllowedForUser(bool $aplicarConsumoReal, ?Bodega $bodega): void
    {
        if (! $aplicarConsumoReal || ! $this->user instanceof User || ! $this->user->requiresAssignedConsumptionWarehouse()) {
            return;
        }

        if (! $this->user->hasAssignedConsumptionWarehouse()) {
            throw new \RuntimeException('Tu usuario notificador no tiene una bodega asignada para consumo.');
        }

        if (! $bodega || (int) $bodega->id !== (int) $this->user->bodega_id_consumo) {
            $nombreBodega = $this->user->bodegaConsumo?->nombre ?: 'la bodega asignada';
            throw new \RuntimeException('Solo puedes importar consumos reales desde tu bodega asignada: ' . $nombreBodega . '.');
        }
    }

    private function obtenerValor(Collection $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if ($row->has($key)) {
                $value = $row->get($key);
                if ($value !== null && trim((string) $value) !== '') {
                    return $value;
                }
            }
        }

        $normalizedRow = [];
        foreach ($row as $rowKey => $rowValue) {
            $normalizedRow[$this->normalizarEncabezado((string) $rowKey)] = $rowValue;
        }

        foreach ($keys as $key) {
            $normalizedKey = $this->normalizarEncabezado($key);

            if (array_key_exists($normalizedKey, $normalizedRow)) {
                $value = $normalizedRow[$normalizedKey];
                if ($value !== null && trim((string) $value) !== '') {
                    return $value;
                }
            }
        }

        foreach ($keys as $key) {
            $normalizedKey = $this->normalizarEncabezado($key);

            foreach ($normalizedRow as $rowKey => $rowValue) {
                if (
                    $rowKey !== ''
                    && (
                        str_contains($rowKey, $normalizedKey)
                        || str_contains($normalizedKey, $rowKey)
                    )
                ) {
                    if ($rowValue !== null && trim((string) $rowValue) !== '') {
                        return $rowValue;
                    }
                }
            }
        }

        return null;
    }

    private function normalizarEncabezado(string $value): string
    {
        $value = str_replace(["\xEF\xBB\xBF", "\xC2\xA0"], '', $value);
        $value = preg_replace('/[[:^print:]]/u', '', $value) ?? $value;
        $value = mb_strtolower(trim($value));
        $value = str_replace([' ', '-', '.', '/', '\\'], '_', $value);
        $value = preg_replace('/_+/', '_', $value) ?? $value;

        return trim($value, '_');
    }


    private function limpiarTexto(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = trim((string) $value);
        $normalized = str_replace(' ', '', $normalized);

        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
            $thousandsSeparator = $decimalSeparator === ',' ? '.' : ',';

            $normalized = str_replace($thousandsSeparator, '', $normalized);
            if ($decimalSeparator === ',') {
                $normalized = str_replace(',', '.', $normalized);
            }
        } elseif ($lastComma !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function toBoolean(mixed $value, bool $default = false): bool
    {
        $text = mb_strtolower($this->limpiarTexto($value));

        if ($text === '') {
            return $default;
        }

        return in_array($text, ['1', 'si', 'sí', 'true', 'yes', 'y', 'aplicar'], true);
    }

    private function normalizarFecha(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->startOfDay();
            }

            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizarLote(mixed $value): ?string
    {
        $text = $this->limpiarTexto($value);

        return $text !== '' ? $text : null;
    }

    private function filtrarColumnasPersistidas(string $table, array $payload): array
    {
        if (! isset($this->columnasPorTabla[$table])) {
            $this->columnasPorTabla[$table] = array_flip(Schema::getColumnListing($table));
        }

        return array_intersect_key($payload, $this->columnasPorTabla[$table]);
    }
}