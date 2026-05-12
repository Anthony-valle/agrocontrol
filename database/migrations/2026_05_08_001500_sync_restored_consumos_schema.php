<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consumos')) {
            Schema::create('consumos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->unsignedBigInteger('cultivo_id')->nullable();
                $table->date('fecha_consumo')->nullable();
                $table->decimal('total', 12, 2)->default(0);
                $table->string('estado')->default('PENDIENTE');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('validated_by')->nullable();
                $table->unsignedBigInteger('anulado_by')->nullable();
                $table->timestamp('fecha_anulacion')->nullable();
                $table->string('motivo_anulacion', 255)->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('consumos', function (Blueprint $table) {
                if (! Schema::hasColumn('consumos', 'empresa_id')) {
                    $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
                }

                if (! Schema::hasColumn('consumos', 'cultivo_id')) {
                    $table->unsignedBigInteger('cultivo_id')->nullable()->after('empresa_id');
                }

                if (! Schema::hasColumn('consumos', 'fecha_consumo')) {
                    $table->date('fecha_consumo')->nullable()->after('cultivo_id');
                }

                if (! Schema::hasColumn('consumos', 'total')) {
                    $table->decimal('total', 12, 2)->default(0)->after('fecha_consumo');
                }

                if (! Schema::hasColumn('consumos', 'estado')) {
                    $table->string('estado')->default('PENDIENTE')->after('total');
                }

                if (! Schema::hasColumn('consumos', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('estado');
                }

                if (! Schema::hasColumn('consumos', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }

                if (! Schema::hasColumn('consumos', 'validated_by')) {
                    $table->unsignedBigInteger('validated_by')->nullable()->after('updated_by');
                }

                if (! Schema::hasColumn('consumos', 'anulado_by')) {
                    $table->unsignedBigInteger('anulado_by')->nullable()->after('validated_by');
                }

                if (! Schema::hasColumn('consumos', 'fecha_anulacion')) {
                    $table->timestamp('fecha_anulacion')->nullable()->after('anulado_by');
                }

                if (! Schema::hasColumn('consumos', 'motivo_anulacion')) {
                    $table->string('motivo_anulacion', 255)->nullable()->after('fecha_anulacion');
                }
            });
        }

        if (! Schema::hasTable('consumo_detalles')) {
            Schema::create('consumo_detalles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('consumo_id')->nullable();
                $table->string('categoria')->nullable();
                $table->string('descripcion')->nullable();
                $table->decimal('cantidad', 12, 2)->default(0);
                $table->string('unidad_medida')->nullable();
                $table->decimal('costo_unitario', 12, 2)->default(0);
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->unsignedBigInteger('insumo_id')->nullable();
                $table->unsignedBigInteger('bodega_id')->nullable();
                $table->string('lote')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('consumo_detalles', function (Blueprint $table) {
                if (! Schema::hasColumn('consumo_detalles', 'consumo_id')) {
                    $table->unsignedBigInteger('consumo_id')->nullable()->after('id');
                }

                if (! Schema::hasColumn('consumo_detalles', 'categoria')) {
                    $table->string('categoria')->nullable()->after('consumo_id');
                }

                if (! Schema::hasColumn('consumo_detalles', 'descripcion')) {
                    $table->string('descripcion')->nullable()->after('categoria');
                }

                if (! Schema::hasColumn('consumo_detalles', 'cantidad')) {
                    $table->decimal('cantidad', 12, 2)->default(0)->after('descripcion');
                }

                if (! Schema::hasColumn('consumo_detalles', 'unidad_medida')) {
                    $table->string('unidad_medida')->nullable()->after('cantidad');
                }

                if (! Schema::hasColumn('consumo_detalles', 'costo_unitario')) {
                    $table->decimal('costo_unitario', 12, 2)->default(0)->after('unidad_medida');
                }

                if (! Schema::hasColumn('consumo_detalles', 'subtotal')) {
                    $table->decimal('subtotal', 12, 2)->default(0)->after('costo_unitario');
                }

                if (! Schema::hasColumn('consumo_detalles', 'insumo_id')) {
                    $table->unsignedBigInteger('insumo_id')->nullable()->after('subtotal');
                }

                if (! Schema::hasColumn('consumo_detalles', 'bodega_id')) {
                    $table->unsignedBigInteger('bodega_id')->nullable()->after('insumo_id');
                }

                if (! Schema::hasColumn('consumo_detalles', 'lote')) {
                    $table->string('lote')->nullable()->after('bodega_id');
                }

                if (! Schema::hasColumn('consumo_detalles', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('lote');
                }

                if (! Schema::hasColumn('consumo_detalles', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('consumo_detalles');
        Schema::dropIfExists('consumos');
    }
};