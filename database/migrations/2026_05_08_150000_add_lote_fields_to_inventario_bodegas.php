<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_bodegas', function (Blueprint $table) {
            if (! Schema::hasColumn('inventario_bodegas', 'numero_lote')) {
                $table->string('numero_lote', 100)->nullable()->after('bodega_id');
            }

            if (! Schema::hasColumn('inventario_bodegas', 'fecha_fabricacion')) {
                $table->date('fecha_fabricacion')->nullable()->after('costo_promedio');
            }

            if (! Schema::hasColumn('inventario_bodegas', 'fecha_vencimiento')) {
                $table->date('fecha_vencimiento')->nullable()->after('fecha_fabricacion');
            }
        });

        try {
            Schema::table('inventario_bodegas', function (Blueprint $table) {
                $table->dropUnique('inventario_bodegas_insumo_id_bodega_id_unique');
            });
        } catch (Throwable $e) {
            // El indice puede no existir o tener otro nombre en esquemas restaurados.
        }

        try {
            Schema::table('inventario_bodegas', function (Blueprint $table) {
                $table->unique(['insumo_id', 'bodega_id', 'numero_lote'], 'inventario_bodegas_insumo_bodega_lote_unique');
            });
        } catch (Throwable $e) {
            // Si ya existe o el motor no lo permite, no bloqueamos la migracion.
        }

        if (Schema::hasTable('movimiento_inventarios')) {
            $movimientos = DB::table('movimiento_inventarios')
                ->select('insumo_id', 'bodega_destino_id', 'numero_lote', 'fecha_fabricacion', 'fecha_vencimiento')
                ->whereNotNull('bodega_destino_id')
                ->whereNotNull('numero_lote')
                ->orderByDesc('id')
                ->get();

            foreach ($movimientos as $movimiento) {
                $query = DB::table('inventario_bodegas')
                    ->where('insumo_id', $movimiento->insumo_id)
                    ->where('bodega_id', $movimiento->bodega_destino_id)
                    ->whereNull('numero_lote');

                if ($query->exists()) {
                    $query->update([
                        'numero_lote' => $movimiento->numero_lote,
                        'fecha_fabricacion' => $movimiento->fecha_fabricacion,
                        'fecha_vencimiento' => $movimiento->fecha_vencimiento,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        try {
            Schema::table('inventario_bodegas', function (Blueprint $table) {
                $table->dropUnique('inventario_bodegas_insumo_bodega_lote_unique');
            });
        } catch (Throwable $e) {
        }

        try {
            Schema::table('inventario_bodegas', function (Blueprint $table) {
                $table->unique(['insumo_id', 'bodega_id'], 'inventario_bodegas_insumo_id_bodega_id_unique');
            });
        } catch (Throwable $e) {
        }

        Schema::table('inventario_bodegas', function (Blueprint $table) {
            if (Schema::hasColumn('inventario_bodegas', 'fecha_vencimiento')) {
                $table->dropColumn('fecha_vencimiento');
            }

            if (Schema::hasColumn('inventario_bodegas', 'fecha_fabricacion')) {
                $table->dropColumn('fecha_fabricacion');
            }

            if (Schema::hasColumn('inventario_bodegas', 'numero_lote')) {
                $table->dropColumn('numero_lote');
            }
        });
    }
};