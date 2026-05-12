<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->tables() as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'deleted_at')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (!Schema::hasColumn($table, 'deleted_by')) {
                    $blueprint->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
                }
                if (!Schema::hasColumn($table, 'delete_reason')) {
                    $blueprint->text('delete_reason')->nullable()->after('deleted_by');
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'delete_reason')) {
                    $blueprint->dropColumn('delete_reason');
                }
                if (Schema::hasColumn($table, 'deleted_by')) {
                    $blueprint->dropColumn('deleted_by');
                }
            });
        }
    }

    private function tables(): array
    {
        return [
            'preparacion_suelo_actividades',
            'empresas',
            'sucursales',
            'users',
            'roles',
            'categorias',
            'cultivos',
            'labores',
            'lotes',
            'insumos',
            'bodegas',
            'planes_cultivos',
            'planes_detalles',
            'consumos',
            'consumo_detalles',
            'cosechas',
            'cosecha_facturas',
            'movimiento_inventarios',
            'factura_inventarios',
        ];
    }
};