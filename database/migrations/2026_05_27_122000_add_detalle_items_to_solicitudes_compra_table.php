<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('solicitudes_compra')) {
            return;
        }

        if (! Schema::hasColumn('solicitudes_compra', 'detalle_items')) {
            Schema::table('solicitudes_compra', function (Blueprint $table) {
                $table->json('detalle_items')->nullable()->after('descripcion');
            });
        }

        DB::table('solicitudes_compra')
            ->whereNull('detalle_items')
            ->orderBy('id')
            ->chunkById(100, function ($solicitudes): void {
                foreach ($solicitudes as $solicitud) {
                    $detalle = [[
                        'insumo_id' => $solicitud->insumo_id,
                        'descripcion' => $solicitud->asunto,
                        'unidad' => $solicitud->unidad,
                        'cantidad' => (float) ($solicitud->cantidad ?? 0),
                        'precio_estimado' => $solicitud->precio_estimado !== null ? (float) $solicitud->precio_estimado : null,
                    ]];

                    DB::table('solicitudes_compra')
                        ->where('id', $solicitud->id)
                        ->update(['detalle_items' => json_encode($detalle, JSON_UNESCAPED_UNICODE)]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('solicitudes_compra') || ! Schema::hasColumn('solicitudes_compra', 'detalle_items')) {
            return;
        }

        Schema::table('solicitudes_compra', function (Blueprint $table) {
            $table->dropColumn('detalle_items');
        });
    }
};