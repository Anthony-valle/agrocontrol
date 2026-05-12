<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'sucursales',
            'bodegas',
            'categorias',
            'insumos',
            'inventario_bodegas',
            'movimiento_inventarios',
            'factura_inventarios',
            'lotes',
            'cultivos',
            'labores',
            'planes_cultivos',
            'consumos',
            'cosechas',
            'cosecha_facturas',
            'notificaciones',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            if (!Schema::hasColumn($table, 'empresa_id')) {
                continue;
            }

            if (!Schema::hasColumn($table, 'empresa_consecutivo')) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->unsignedBigInteger('empresa_consecutivo')->nullable()->after('empresa_id');
                });
            }

            $this->backfillConsecutivo($table);

            $indexName = $table . '_empresa_id_consecutivo_unique';
            if (!$this->indexExists($table, $indexName)) {
                Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName) {
                    $tableBlueprint->unique(['empresa_id', 'empresa_consecutivo'], $indexName);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'sucursales',
            'bodegas',
            'categorias',
            'insumos',
            'inventario_bodegas',
            'movimiento_inventarios',
            'factura_inventarios',
            'lotes',
            'cultivos',
            'labores',
            'planes_cultivos',
            'consumos',
            'cosechas',
            'cosecha_facturas',
            'notificaciones',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $indexName = $table . '_empresa_id_consecutivo_unique';
            if ($this->indexExists($table, $indexName)) {
                Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName) {
                    $tableBlueprint->dropUnique($indexName);
                });
            }

            if (Schema::hasColumn($table, 'empresa_consecutivo')) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->dropColumn('empresa_consecutivo');
                });
            }
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(1) AS total
             FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return ((int) ($result->total ?? 0)) > 0;
    }

    private function backfillConsecutivo(string $table): void
    {
        if (!Schema::hasColumn($table, 'id') || !Schema::hasColumn($table, 'empresa_id')) {
            return;
        }

        DB::statement('SET @empresa_actual := 0, @consecutivo := 0');

        DB::statement("\n            UPDATE {$table} t\n            INNER JOIN (\n                SELECT\n                    id,\n                    empresa_id,\n                    (@consecutivo := IF(@empresa_actual = empresa_id, @consecutivo + 1, 1)) AS nuevo_consecutivo,\n                    (@empresa_actual := empresa_id) AS _empresa_tracker\n                FROM {$table}\n                WHERE empresa_id IS NOT NULL\n                ORDER BY empresa_id, id\n            ) calc ON calc.id = t.id\n            SET t.empresa_consecutivo = COALESCE(t.empresa_consecutivo, calc.nuevo_consecutivo)\n        ");
    }
};
