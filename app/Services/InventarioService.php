<?php

namespace App\Services;

use App\Models\InventarioBodega;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventarioService
{
    private array $columnasTablaCache = [];

    public function revertirStockDeConsumo(\App\Models\Consumo $consumo): void
    {
        $consumo->loadMissing('detalles');

        $registros = $this->obtenerRegistrosReversionConsumo($consumo);

        foreach ($registros as $registro) {
            if (empty($registro->insumo_id) || empty($registro->bodega_origen_id)) {
                continue;
            }

            $inventario = $this->buscarInventarioPorLote(
                (int) $registro->insumo_id,
                (int) $registro->bodega_origen_id,
                $this->normalizarValorLote($registro->numero_lote ?? null)
            );

            if ($inventario) {
                $this->actualizarInventarioPersistido($inventario, $this->filtrarColumnasPersistidas('inventario_bodegas', [
                    'stock_actual' => $inventario->stock_actual + (float) $registro->cantidad,
                ]));
            }
        }
    }

    public function eliminarMovimientosDeConsumo(int $consumoId): void
    {
        if (! $this->tablaTieneColumna('movimiento_inventarios', 'consumo_id')) {
            return;
        }

        MovimientoInventario::where('consumo_id', $consumoId)->delete();
    }

    public function registrarConsumo(array $data)
    {
        $consumo = \App\Models\Consumo::findOrFail($data['consumo_id']);

        DB::transaction(function () use ($data, $consumo) {
            foreach ($data['items'] as $item) {
                $insumoId = $item['id'];
                $cantidad = $item['cantidad'];
                $lote = $this->normalizarValorLote($item['lote'] ?? null);

                if (empty($item['bodega_id'])) {
                    continue;
                }

                $this->validateAssignedWarehouseAccess((int) $item['bodega_id']);

                $inventario = $this->buscarInventarioPorLote($insumoId, (int) $item['bodega_id'], $lote);

                if (!$inventario) {
                    throw new \Exception("No hay inventario para {$item['nombre']} en la bodega/lote seleccionado.");
                }

                if ($inventario->stock_actual < $cantidad) {
                    throw new \Exception("Stock insuficiente para {$item['nombre']} en lote {$item['lote']}.");
                }

                $stockAnterior = $inventario->stock_actual;
                $inventario->stock_actual -= $cantidad;
                $this->actualizarInventarioPersistido($inventario, $this->filtrarColumnasPersistidas('inventario_bodegas', [
                    'stock_actual' => $inventario->stock_actual,
                ]));

                // Registrar movimiento
                MovimientoInventario::create($this->filtrarColumnasPersistidas('movimiento_inventarios', [
                    'empresa_id' => $consumo->empresa_id,
                    'insumo_id' => $insumoId,
                    'tipo' => 'CONSUMO',
                    'cantidad' => $cantidad,
                    'consumo_id'  => $data['consumo_id'],
                    'precio_unitario' => $inventario->costo_promedio,
                    'costo_unitario' => $inventario->costo_promedio,
                    'stock_anterior' => $stockAnterior,
                    'stock_actual' => $inventario->stock_actual,
                    'descripcion' => 'Consumo a cultivo',
                    'sucursal_id' => $consumo->cultivo->lote->sucursal_id ?? 1,
                    'bodega_origen_id' => $item['bodega_id'],
                    'numero_lote' => $lote,
                    'created_by' => Auth::id(),
                ]));
            }
        });
    }

    public function revertirConsumo(int $consumoId): void
    {
        $consumo = \App\Models\Consumo::with('detalles')->findOrFail($consumoId);

        DB::transaction(function () use ($consumoId, $consumo) {
            $this->revertirStockDeConsumo($consumo);

            $this->eliminarMovimientosDeConsumo($consumoId);
            $consumo->detalles()->delete();
            $consumo->delete();
        });
    }

    public function restaurarConsumo(int $consumoId): void
    {
        $consumo = \App\Models\Consumo::onlyTrashed()->findOrFail($consumoId);

        DB::transaction(function () use ($consumoId, $consumo) {
            $consumo->restore();
            \App\Models\Consumo_detalles::onlyTrashed()->where('consumo_id', $consumoId)->restore();

            if ($this->tablaTieneColumna('movimiento_inventarios', 'consumo_id')) {
                MovimientoInventario::onlyTrashed()->where('consumo_id', $consumoId)->restore();
            }

            $consumo->load('detalles');
            $this->registrarConsumo([
                'consumo_id' => $consumoId,
                'cultivo_id' => $consumo->cultivo_id,
                'items' => $consumo->detalles->map(function ($detalle) {
                    return [
                        'id' => $detalle->insumo_id,
                        'nombre' => $detalle->descripcion,
                        'cantidad' => (float) $detalle->cantidad,
                        'precio' => (float) $detalle->costo_unitario,
                        'bodega_id' => $detalle->bodega_id,
                        'lote' => $detalle->lote,
                    ];
                })->values()->all(),
            ]);
        });
    }

    private function obtenerRegistrosReversionConsumo(\App\Models\Consumo $consumo)
    {
        if ($this->tablaTieneColumna('movimiento_inventarios', 'consumo_id')) {
            return MovimientoInventario::query()
                ->where('consumo_id', $consumo->id)
                ->get(['insumo_id', 'bodega_origen_id', 'numero_lote', 'cantidad']);
        }

        return $consumo->detalles->map(function ($detalle) {
            return (object) [
                'insumo_id' => $detalle->insumo_id,
                'bodega_origen_id' => $detalle->bodega_id,
                'numero_lote' => $detalle->lote,
                'cantidad' => (float) $detalle->cantidad,
            ];
        });
    }

    private function buscarInventarioPorLote(int $insumoId, int $bodegaId, ?string $lote): ?InventarioBodega
    {
        return InventarioBodega::query()
            ->where('insumo_id', $insumoId)
            ->where('bodega_id', $bodegaId)
            ->when(
                $this->tablaTieneColumna('inventario_bodegas', 'numero_lote'),
                function ($query) use ($lote) {
                    if ($lote === null) {
                        $query->whereNull('numero_lote');
                    } else {
                        $query->where('numero_lote', $lote);
                    }
                }
            )
            ->first();
    }

    private function filtrarColumnasPersistidas(string $tabla, array $payload): array
    {
        $columnas = $this->obtenerColumnasTabla($tabla);

        return array_filter(
            $payload,
            static fn ($valor, $columna) => in_array($columna, $columnas, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function obtenerColumnasTabla(string $tabla): array
    {
        if (!isset($this->columnasTablaCache[$tabla])) {
            $this->columnasTablaCache[$tabla] = Schema::getColumnListing($tabla);
        }

        return $this->columnasTablaCache[$tabla];
    }

    private function tablaTieneColumna(string $tabla, string $columna): bool
    {
        return in_array($columna, $this->obtenerColumnasTabla($tabla), true);
    }

    private function actualizarInventarioPersistido(InventarioBodega $inventario, array $payload): void
    {
        if ($payload === []) {
            return;
        }

        if ($this->tablaTieneColumna('inventario_bodegas', 'updated_at') && !array_key_exists('updated_at', $payload)) {
            $payload['updated_at'] = now();
        }

        $query = InventarioBodega::query()
            ->where('insumo_id', $inventario->insumo_id)
            ->where('bodega_id', $inventario->bodega_id);

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

    private function normalizarValorLote(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '' || $texto === '__SIN_LOTE__') {
            return null;
        }

        return $texto;
    }

    private function validateAssignedWarehouseAccess(int $bodegaId): void
    {
        $user = Auth::user();

        if (! $user instanceof \App\Models\User || ! $user->requiresAssignedConsumptionWarehouse()) {
            return;
        }

        if (! $user->hasAssignedConsumptionWarehouse()) {
            throw new \RuntimeException('Tu usuario notificador no tiene una bodega asignada para consumo.');
        }

        if ((int) $user->bodega_id_consumo !== $bodegaId) {
            $user->loadMissing('bodegaConsumo');
            $nombreBodega = $user->bodegaConsumo?->nombre ?: 'la bodega asignada';

            throw new \RuntimeException('Solo puedes consumir desde tu bodega asignada: ' . $nombreBodega . '.');
        }
    }
}