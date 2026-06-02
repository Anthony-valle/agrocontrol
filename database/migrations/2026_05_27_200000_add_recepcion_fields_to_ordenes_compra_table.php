<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ordenes_compra')) {
            return;
        }

        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (! Schema::hasColumn('ordenes_compra', 'recibido_por')) {
                $table->unsignedBigInteger('recibido_por')->nullable()->after('generado_por');
            }

            if (! Schema::hasColumn('ordenes_compra', 'recibido_en')) {
                $table->timestamp('recibido_en')->nullable()->after('fecha_emision');
            }

            if (! Schema::hasColumn('ordenes_compra', 'recepcion_estado')) {
                $table->string('recepcion_estado', 40)->nullable()->after('estado');
            }

            if (! Schema::hasColumn('ordenes_compra', 'recepcion_observaciones')) {
                $table->text('recepcion_observaciones')->nullable()->after('observaciones');
            }

            if (! Schema::hasColumn('ordenes_compra', 'diferencias_aprobadas_por')) {
                $table->unsignedBigInteger('diferencias_aprobadas_por')->nullable()->after('recibido_por');
            }

            if (! Schema::hasColumn('ordenes_compra', 'diferencias_aprobadas_en')) {
                $table->timestamp('diferencias_aprobadas_en')->nullable()->after('recibido_en');
            }

            if (! Schema::hasColumn('ordenes_compra', 'diferencias_observaciones')) {
                $table->text('diferencias_observaciones')->nullable()->after('recepcion_observaciones');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ordenes_compra')) {
            return;
        }

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $columnas = [
                'recibido_por',
                'recibido_en',
                'recepcion_estado',
                'recepcion_observaciones',
                'diferencias_aprobadas_por',
                'diferencias_aprobadas_en',
                'diferencias_observaciones',
            ];

            $existentes = array_values(array_filter($columnas, fn ($columna) => Schema::hasColumn('ordenes_compra', $columna)));

            if ($existentes !== []) {
                $table->dropColumn($existentes);
            }
        });
    }
};