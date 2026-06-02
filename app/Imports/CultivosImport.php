<?php

namespace App\Imports;

use App\Models\Bodega;
use App\Models\Consumo;
use App\Models\Consumo_detalles;
use App\Models\Cultivo;
use App\Models\Insumo;
use App\Models\Lote;
use App\Services\InventarioService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CultivosImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private int $userId;

    private array $stats = [
        'filas_procesadas' => 0,
        'filas_importadas' => 0,
        'filas_omitidas' => 0,
        'filas_error' => 0,
        'consumos_multiplicados_por_hectareas' => 0,
    ];

    private array $errores = [];

    private array $hectareasAcumuladasPorLote = [];

    private array $columnasPorTabla = [];

    private array $cultivoIdsPorCodigo = [];

    private InventarioService $inventarioService;

    public function __construct(int $userId, ?InventarioService $inventarioService = null)
    {
        $this->userId = $userId;
        $this->inventarioService = $inventarioService ?? app(InventarioService::class);
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $this->stats['filas_procesadas']++;
            $fila = $index + 2;

            try {
                $codigo = $this->limpiarTexto($this->obtenerValor($row, ['codigo', 'codigo_cultivo']));
                if ($codigo === '') {
                    throw new \RuntimeException('La columna codigo es obligatoria para identificar el cultivo.');
                }

                $cultivo = null;
                $hectareasAgregadas = 0.0;

                DB::transaction(function () use ($row, $codigo, &$cultivo, &$hectareasAgregadas) {
                    [$cultivo, $hectareasAgregadas] = $this->resolverOCrearCultivo($row, $codigo);
                    $this->registrarConsumoHistoricoSiAplica($row, $cultivo);
                });

                if (! $cultivo instanceof Cultivo) {
                    throw new \RuntimeException('No se pudo resolver el cultivo importado.');
                }

                $this->cultivoIdsPorCodigo[$codigo] = $cultivo->id;

                if ($hectareasAgregadas > 0) {
                    $this->hectareasAcumuladasPorLote[$cultivo->lotes_id] = ($this->hectareasAcumuladasPorLote[$cultivo->lotes_id] ?? 0) + $hectareasAgregadas;
                }

                $this->stats['filas_importadas']++;
            } catch (\Throwable $error) {
                $this->stats['filas_error']++;
                $this->errores[] = 'Fila ' . $fila . ': ' . $error->getMessage();
            }
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
            'Filas importadas: ' . $this->stats['filas_importadas'],
            'Cultivos creados: ' . ($this->stats['cultivos_creados'] ?? 0),
            'Consumos historicos creados: ' . ($this->stats['consumos_creados'] ?? 0),
            'Consumos reales aplicados a bodega: ' . ($this->stats['consumos_reales_aplicados'] ?? 0),
            'Consumos historicos sin descuento: ' . ($this->stats['consumos_historicos_sin_descuento'] ?? 0),
            'Consumos multiplicados por hectareas del cultivo: ' . ($this->stats['consumos_multiplicados_por_hectareas'] ?? 0),
            'Filas con error: ' . $this->stats['filas_error'],
        ];
    }

    private function resolverOCrearCultivo(Collection $row, string $codigo): array
    {
        if (isset($this->cultivoIdsPorCodigo[$codigo])) {
            $cultivo = Cultivo::query()->findOrFail($this->cultivoIdsPorCodigo[$codigo]);

            return [$cultivo, 0.0];
        }

        $existente = Cultivo::query()->where('codigo', $codigo)->first();
        if ($existente) {
            return [$existente, 0.0];
        }

        $nombre = $this->limpiarTexto($this->obtenerValor($row, ['nombre', 'nombre_cultivo', 'cultivo']));
        $variedad = $this->limpiarTexto($this->obtenerValor($row, ['variedad']));
        $ciclo = $this->limpiarTexto($this->obtenerValor($row, ['ciclo']));
        $fechaSiembra = $this->normalizarFecha($this->obtenerValor($row, ['fecha_siembra', 'siembra_fecha']));
        $duracionCiclo = (int) $this->toFloat($this->obtenerValor($row, ['duracion_ciclo', 'duracion_dias', 'duracion']));
        $hectareas = round($this->toFloat($this->obtenerValor($row, ['hectareas', 'hectareas_sembradas', 'area_sembrada'])), 3);
        $cosechaEstimada = round($this->toFloat($this->obtenerValor($row, ['cosecha_estimada', 'rendimiento_estimado'])), 3);
        $unidadMedida = $this->limpiarTexto($this->obtenerValor($row, ['unidad_medida', 'unidad']));
        $estado = $this->normalizarEstado($this->obtenerValor($row, ['estado'])) ?: 'Cerrado';
        $observaciones = $this->limpiarTexto($this->obtenerValor($row, ['observaciones', 'observacion', 'notas']));

        if ($nombre === '' || $variedad === '' || $ciclo === '' || ! $fechaSiembra || $duracionCiclo < 1 || $unidadMedida === '') {
            throw new \RuntimeException('Para crear un cultivo nuevo se requieren: nombre, variedad, ciclo, fecha_siembra, duracion_ciclo y unidad_medida.');
        }

        $lote = $this->resolverLote(
            $this->obtenerValor($row, ['lote_id']),
            $this->obtenerValor($row, ['lote_nombre', 'lote'])
        );

        if (! $lote) {
            throw new \RuntimeException('No se encontro el lote. Envie lote_id o lote_nombre con un valor existente.');
        }

        $this->validarHectareasDisponibles($lote, $hectareas);

        $cultivo = Cultivo::create($this->filtrarColumnasPersistidas('cultivos', [
            'empresa_id' => $lote->empresa_id,
            'lotes_id' => $lote->id,
            'codigo' => $codigo,
            'nombre' => $nombre,
            'variedad' => $variedad,
            'ciclo' => $ciclo,
            'fecha_siembra' => $fechaSiembra->toDateString(),
            'duracion_ciclo' => $duracionCiclo,
            'fecha_cosecha' => $fechaSiembra->copy()->addDays($duracionCiclo)->toDateString(),
            'hectareas' => $hectareas > 0 ? $hectareas : null,
            'cosecha_estimada' => $cosechaEstimada > 0 ? $cosechaEstimada : null,
            'unidad_medida' => $unidadMedida,
            'estado' => $estado,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
            'created_by' => $this->userId,
        ]));

        $this->stats['cultivos_creados'] = ($this->stats['cultivos_creados'] ?? 0) + 1;

        return [$cultivo, $hectareas];
    }

    private function registrarConsumoHistoricoSiAplica(Collection $row, Cultivo $cultivo): void
    {
        if (! $this->filaTieneDatosDeConsumo($row)) {
            return;
        }

        $fechaConsumo = $this->normalizarFecha($this->obtenerValor($row, ['fecha_consumo', 'consumo_fecha']));
        if (! $fechaConsumo) {
            throw new \RuntimeException('Si la fila incluye consumo, fecha_consumo es obligatoria.');
        }

        $aplicarConsumoReal = $this->toBoolean($this->obtenerValor($row, ['aplicar_consumo_real_bodega', 'hacer_consumo_real_bodega', 'consumo_real_bodega']), false);
        $cantidadPorHectarea = round($this->toFloat($this->obtenerValor($row, ['cantidad_por_ha', 'cantidad_consumo', 'cantidad', 'cantidad_ha', 'cantidad_por_hectarea'])), 3);
        if ($cantidadPorHectarea <= 0) {
            throw new \RuntimeException('cantidad_por_ha debe ser mayor a 0 cuando se registra un consumo historico.');
        }

        $hectareasCultivo = round($this->toFloat($cultivo->hectareas ?? null), 3);
        if ($hectareasCultivo <= 0) {
            throw new \RuntimeException('El cultivo no tiene hectareas validas para multiplicar el consumo por HA.');
        }

        $cantidad = round($cantidadPorHectarea * $hectareasCultivo, 3);
        $this->stats['consumos_multiplicados_por_hectareas'] = ($this->stats['consumos_multiplicados_por_hectareas'] ?? 0) + 1;

        $insumo = $this->resolverInsumo(
            $this->obtenerValor($row, ['insumo_codigo', 'codigo_insumo']),
            $this->obtenerValor($row, ['insumo_nombre', 'nombre_insumo', 'insumo'])
        );

        if ($aplicarConsumoReal && ! $insumo) {
            throw new \RuntimeException('Para aplicar consumo real de bodega debes enviar insumo_codigo o insumo_nombre valido.');
        }

        $bodega = $this->resolverBodega(
            $this->obtenerValor($row, ['bodega_id', 'almacen_id']),
            $this->obtenerValor($row, ['bodega_nombre', 'bodega', 'almacen', 'almacen_nombre'])
        );

        if ($aplicarConsumoReal && ! $bodega) {
            throw new \RuntimeException('Para aplicar consumo real de bodega debes enviar bodega_id o bodega_nombre valido.');
        }

        $descripcion = $this->limpiarTexto($this->obtenerValor($row, ['descripcion_consumo', 'descripcion'])) ?: ($insumo->nombre ?? 'Consumo historico importado');
        $categoria = $this->limpiarTexto($this->obtenerValor($row, ['categoria_consumo']))
            ?: ($insumo->categoria_nombre ?? 'Otros Insumos');
        $unidad = $this->limpiarTexto($this->obtenerValor($row, ['unidad_consumo']))
            ?: ($insumo->unidad_medida ?? $cultivo->unidad_medida ?? '');
        $costoUnitario = round($this->toFloat($this->obtenerValor($row, ['costo_unitario_consumo', 'costo_unitario', 'precio_consumo'])), 3);
        if ($costoUnitario <= 0 && $insumo) {
            $costoUnitario = round((float) ($insumo->costo_estimado ?? 0), 3);
        }

        if ($unidad === '') {
            throw new \RuntimeException('unidad_consumo es obligatoria cuando el insumo no la puede resolver automaticamente.');
        }

        $lote = $this->normalizarLote($this->obtenerValor($row, ['lote_consumo', 'lote', 'numero_lote']));
        $subtotal = round($cantidad * $costoUnitario, 3);

        $consumo = Consumo::create($this->filtrarColumnasPersistidas('consumos', [
            'empresa_id' => $cultivo->empresa_id,
            'cultivo_id' => $cultivo->id,
            'fecha_consumo' => $fechaConsumo->toDateString(),
            'total' => $subtotal,
            'estado' => 'FINALIZADO',
            'created_by' => $this->userId,
            'validated_by' => $this->userId,
        ]));

        Consumo_detalles::create($this->filtrarColumnasPersistidas('consumo_detalles', [
            'consumo_id' => $consumo->id,
            'insumo_id' => $insumo?->id,
            'categoria' => $categoria,
            'descripcion' => $descripcion,
            'cantidad' => $cantidad,
            'unidad_medida' => $unidad,
            'costo_unitario' => $costoUnitario,
            'subtotal' => $subtotal,
            'bodega_id' => $bodega?->id,
            'lote' => $lote,
            'created_by' => $this->userId,
        ]));

        if ($aplicarConsumoReal) {
            $this->inventarioService->registrarConsumo([
                'consumo_id' => $consumo->id,
                'cultivo_id' => $cultivo->id,
                'items' => [[
                    'id' => $insumo->id,
                    'nombre' => $descripcion,
                    'categoria' => $categoria,
                    'cantidad' => $cantidad,
                    'precio' => $costoUnitario,
                    'unidad' => $unidad,
                    'bodega_id' => $bodega->id,
                    'lote' => $lote,
                ]],
            ]);

            $this->stats['consumos_reales_aplicados'] = ($this->stats['consumos_reales_aplicados'] ?? 0) + 1;
        } else {
            $this->stats['consumos_historicos_sin_descuento'] = ($this->stats['consumos_historicos_sin_descuento'] ?? 0) + 1;
        }

        $this->stats['consumos_creados'] = ($this->stats['consumos_creados'] ?? 0) + 1;
    }

    private function resolverLote(mixed $loteId, mixed $loteNombre): ?Lote
    {
        if (is_numeric($loteId)) {
            $lote = Lote::query()->find((int) $loteId);
            if ($lote) {
                return $lote;
            }
        }

        $nombre = $this->limpiarTexto($loteNombre);

        if ($nombre !== '') {
            return Lote::query()->where('nombre', $nombre)->first();
        }

        return null;
    }

    private function resolverInsumo(mixed $insumoCodigo, mixed $insumoNombre): ?Insumo
    {
        if ($insumoCodigo !== null && trim((string) $insumoCodigo) !== '') {
            $insumo = Insumo::query()->activos()->where('codigo', trim((string) $insumoCodigo))->first();
            if ($insumo) {
                return $insumo;
            }
        }

        $nombre = $this->limpiarTexto($insumoNombre);
        if ($nombre !== '') {
            return Insumo::query()->activos()->where('nombre', $nombre)->first();
        }

        return null;
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

    private function validarHectareasDisponibles(Lote $lote, float $hectareas): void
    {
        if ($hectareas <= 0) {
            return;
        }

        $areaLote = (float) ($lote->area ?? 0);
        if ($areaLote <= 0) {
            return;
        }

        $ocupadas = (float) Cultivo::query()
            ->where('lotes_id', $lote->id)
            ->sum('hectareas');

        $ocupadas += (float) ($this->hectareasAcumuladasPorLote[$lote->id] ?? 0);

        if (($ocupadas + $hectareas) > $areaLote + 0.0001) {
            throw new \RuntimeException('Las hectareas exceden el area disponible del lote ' . $lote->nombre . '.');
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

        return null;
    }

    private function limpiarTexto(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function normalizarEstado(mixed $value): string
    {
        $estado = mb_strtolower($this->limpiarTexto($value));

        return match ($estado) {
            'activo' => 'Activo',
            'inactivo' => 'Inactivo',
            'cerrado' => 'Cerrado',
            default => $this->limpiarTexto($value),
        };
    }

    private function filaTieneDatosDeConsumo(Collection $row): bool
    {
        foreach ([
            'fecha_consumo',
            'aplicar_consumo_real_bodega',
            'insumo_codigo',
            'insumo_nombre',
            'categoria_consumo',
            'descripcion_consumo',
            'cantidad_consumo',
            'cantidad_por_ha',
            'bodega_id',
            'bodega_nombre',
            'lote_consumo',
        ] as $key) {
            if ($row->has($key) && trim((string) $row->get($key)) !== '') {
                return true;
            }
        }

        return false;
    }

    private function toBoolean(mixed $value, bool $default = false): bool
    {
        $text = mb_strtolower($this->limpiarTexto($value));

        if ($text === '') {
            return $default;
        }

        return in_array($text, ['1', 'si', 'sí', 'true', 'yes', 'y', 'aplicar'], true);
    }

    private function normalizarLote(mixed $value): ?string
    {
        $text = $this->limpiarTexto($value);

        return $text !== '' ? $text : null;
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace([' ', ','], ['', '.'], (string) $value);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
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
        } catch (\Throwable $error) {
            return null;
        }
    }

    private function filtrarColumnasPersistidas(string $table, array $payload): array
    {
        if (! isset($this->columnasPorTabla[$table])) {
            $this->columnasPorTabla[$table] = array_flip(Schema::getColumnListing($table));
        }

        return array_intersect_key($payload, $this->columnasPorTabla[$table]);
    }
}