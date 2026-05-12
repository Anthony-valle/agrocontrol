<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventario_bodegas')) {
            return;
        }

        $indices = collect(DB::select("SHOW INDEX FROM inventario_bodegas"))
            ->groupBy('Key_name');

        $indiceLegacy = $indices->get('inventario_bodegas_insumo_id_bodega_id_unique');
        $indicePorLote = $indices->get('inventario_bodegas_insumo_bodega_lote_unique');

        if ($indiceLegacy && $indiceLegacy->count() === 2 && $indicePorLote && $indicePorLote->count() === 3) {
            Schema::table('inventario_bodegas', function (Blueprint $table) {
                $table->dropUnique('inventario_bodegas_insumo_id_bodega_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventario_bodegas')) {
            return;
        }

        $indices = collect(DB::select("SHOW INDEX FROM inventario_bodegas"))
            ->groupBy('Key_name');

        if (! $indices->has('inventario_bodegas_insumo_id_bodega_id_unique')) {
            Schema::table('inventario_bodegas', function (Blueprint $table) {
                $table->unique(['insumo_id', 'bodega_id'], 'inventario_bodegas_insumo_id_bodega_id_unique');
            });
        }
    }
};