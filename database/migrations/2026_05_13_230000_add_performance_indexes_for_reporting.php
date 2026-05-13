<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('cultivos', 'cultivos_empresa_lote_estado_siembra_idx', ['empresa_id', 'lotes_id', 'estado', 'fecha_siembra']);

        $this->addIndexIfMissing('consumos', 'consumos_empresa_cultivo_fecha_idx', ['empresa_id', 'cultivo_id', 'fecha_consumo']);
        $this->addIndexIfMissing('consumo_detalles', 'consumo_detalles_consumo_categoria_idx', ['consumo_id', 'categoria']);

        $this->addIndexIfMissing('cosechas', 'cosechas_empresa_cultivo_fecha_idx', ['empresa_id', 'cultivo_id', 'fecha_cosecha']);

        $this->addIndexIfMissing('inventario_bodegas', 'inventario_bodegas_bodega_vencimiento_idx', ['bodega_id', 'fecha_vencimiento']);
        $this->addIndexIfMissing('inventario_bodegas', 'inventario_bodegas_vencimiento_idx', ['fecha_vencimiento']);

        $this->addIndexIfMissing('movimiento_inventarios', 'movimientos_sucursal_created_idx', ['sucursal_id', 'created_at']);
        $this->addIndexIfMissing('movimiento_inventarios', 'movimientos_bodega_origen_created_idx', ['bodega_origen_id', 'created_at']);
        $this->addIndexIfMissing('movimiento_inventarios', 'movimientos_bodega_destino_created_idx', ['bodega_destino_id', 'created_at']);
        $this->addIndexIfMissing('movimiento_inventarios', 'movimientos_insumo_created_idx', ['insumo_id', 'created_at']);
        $this->addIndexIfMissing('movimiento_inventarios', 'movimientos_tipo_created_idx', ['tipo', 'created_at']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('cultivos', 'cultivos_empresa_lote_estado_siembra_idx');

        $this->dropIndexIfExists('consumos', 'consumos_empresa_cultivo_fecha_idx');
        $this->dropIndexIfExists('consumo_detalles', 'consumo_detalles_consumo_categoria_idx');

        $this->dropIndexIfExists('cosechas', 'cosechas_empresa_cultivo_fecha_idx');

        $this->dropIndexIfExists('inventario_bodegas', 'inventario_bodegas_bodega_vencimiento_idx');
        $this->dropIndexIfExists('inventario_bodegas', 'inventario_bodegas_vencimiento_idx');

        $this->dropIndexIfExists('movimiento_inventarios', 'movimientos_sucursal_created_idx');
        $this->dropIndexIfExists('movimiento_inventarios', 'movimientos_bodega_origen_created_idx');
        $this->dropIndexIfExists('movimiento_inventarios', 'movimientos_bodega_destino_created_idx');
        $this->dropIndexIfExists('movimiento_inventarios', 'movimientos_insumo_created_idx');
        $this->dropIndexIfExists('movimiento_inventarios', 'movimientos_tipo_created_idx');
    }

    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $indexName) {
            $tableBlueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName) {
            $tableBlueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};