<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->decimalColumns() as [$table, $column, $precision, $nullable, $default]) {
            $this->modifyDecimalColumn($table, $column, $precision, 3, $nullable, $default);
        }
    }

    public function down(): void
    {
        foreach ($this->decimalColumns() as [$table, $column, $precision, $nullable, $default]) {
            $rollbackDefault = $default;

            if ($rollbackDefault === '0.000') {
                $rollbackDefault = '0.00';
            }

            $this->modifyDecimalColumn($table, $column, $precision, 2, $nullable, $rollbackDefault);
        }
    }

    private function decimalColumns(): array
    {
        return [
            ['consumos', 'total', 12, false, '0.000'],
            ['consumo_detalles', 'cantidad', 12, false, '0.000'],
            ['consumo_detalles', 'costo_unitario', 12, false, '0.000'],
            ['consumo_detalles', 'subtotal', 12, false, '0.000'],
            ['movimientos_inventarios', 'cantidad', 12, false, '0.000'],
            ['movimientos_inventarios', 'stock_anterior', 12, true, null],
            ['movimientos_inventarios', 'stock_actual', 12, true, null],
            ['movimientos_inventarios', 'costo_unitario', 12, false, '0.000'],
            ['movimientos_inventarios', 'precio_unitario', 12, false, '0.000'],
            ['inventarios', 'stock', 10, false, null],
            ['inventario_bodegas', 'stock_actual', 10, false, '0.000'],
            ['inventario_bodegas', 'costo_promedio', 10, false, '0.000'],
            ['insumos', 'stock_minimo', 10, false, '0.000'],
            ['insumos', 'costo_estimado', 10, false, '0.000'],
            ['detalle_entrada_insumos', 'precio_unitario', 10, false, null],
            ['detalle_entrada_insumos', 'subtotal', 10, false, null],
            ['insumo_entradas', 'cantida', 10, true, null],
            ['insumo_entradas', 'costo_unitario', 10, true, null],
            ['insumo_salidas', 'cantidad', 10, false, null],
            ['labores', 'costo_unitario', 12, false, null],
            ['lotes', 'area', 12, true, null],
            ['cultivos', 'hectareas', 12, true, null],
            ['cultivos', 'cosecha_estimada', 12, true, null],
            ['planes_cultivos', 'cosecha_estimada', 12, false, '0.000'],
            ['planes_cultivos', 'total_presupuesto', 12, false, '0.000'],
            ['planes_detalles', 'cantidad_estimada', 12, false, null],
            ['planes_detalles', 'costo_unitario', 12, false, null],
            ['planes_detalles', 'subtotal', 12, false, '0.000'],
            ['cosechas', 'cantidad_bruta', 15, false, null],
            ['cosechas', 'cantidad_descarte', 15, false, '0.000'],
            ['cosechas', 'descarte', 15, false, '0.000'],
            ['cosechas', 'cantidad_neta', 15, false, null],
            ['cosechas', 'cantidad_disponible', 15, false, '0.000'],
            ['cosechas', 'precio_venta_unitario', 15, true, null],
            ['cosecha_facturas', 'cantidad_vendida', 10, false, null],
            ['cosecha_facturas', 'precio_unitario', 10, false, null],
            ['cosecha_facturas', 'total', 12, false, null],
            ['solicitudes_compra', 'cantidad', 12, false, '0.000'],
            ['solicitudes_compra', 'precio_estimado', 12, true, null],
            ['ordenes_compra', 'total_estimado', 12, true, null],
        ];
    }

    private function modifyDecimalColumn(
        string $table,
        string $column,
        int $precision,
        int $scale,
        bool $nullable,
        ?string $default,
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $nullSql = $nullable ? 'NULL' : 'NOT NULL';
        $defaultSql = $default !== null ? ' DEFAULT ' . $default : '';

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` DECIMAL(%d,%d) %s%s',
            $table,
            $column,
            $precision,
            $scale,
            $nullSql,
            $defaultSql,
        ));
    }
};