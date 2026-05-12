<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cultivos')) {
            Schema::create('cultivos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->unsignedBigInteger('lotes_id')->nullable();
                $table->string('nombre');
                $table->string('codigo')->nullable();
                $table->string('variedad')->nullable();
                $table->string('ciclo')->nullable();
                $table->string('unidad_medida')->nullable();
                $table->date('fecha_siembra')->nullable();
                $table->integer('duracion_ciclo')->nullable();
                $table->date('fecha_cosecha')->nullable();
                $table->decimal('hectareas', 8, 2)->nullable();
                $table->decimal('cosecha_estimada', 8, 2)->nullable();
                $table->string('estado')->default('Activo');
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('cultivos', function (Blueprint $table) {
            if (! Schema::hasColumn('cultivos', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('cultivos', 'lotes_id')) {
                $table->unsignedBigInteger('lotes_id')->nullable()->after('empresa_id');
            }

            if (! Schema::hasColumn('cultivos', 'codigo')) {
                $table->string('codigo')->nullable()->after('nombre');
            }

            if (! Schema::hasColumn('cultivos', 'variedad')) {
                $table->string('variedad')->nullable()->after('codigo');
            }

            if (! Schema::hasColumn('cultivos', 'ciclo')) {
                $table->string('ciclo')->nullable()->after('variedad');
            }

            if (! Schema::hasColumn('cultivos', 'unidad_medida')) {
                $table->string('unidad_medida')->nullable()->after('ciclo');
            }

            if (! Schema::hasColumn('cultivos', 'fecha_siembra')) {
                $table->date('fecha_siembra')->nullable()->after('unidad_medida');
            }

            if (! Schema::hasColumn('cultivos', 'duracion_ciclo')) {
                $table->integer('duracion_ciclo')->nullable()->after('fecha_siembra');
            }

            if (! Schema::hasColumn('cultivos', 'fecha_cosecha')) {
                $table->date('fecha_cosecha')->nullable()->after('duracion_ciclo');
            }

            if (! Schema::hasColumn('cultivos', 'hectareas')) {
                $table->decimal('hectareas', 8, 2)->nullable()->after('fecha_cosecha');
            }

            if (! Schema::hasColumn('cultivos', 'cosecha_estimada')) {
                $table->decimal('cosecha_estimada', 8, 2)->nullable()->after('hectareas');
            }

            if (! Schema::hasColumn('cultivos', 'estado')) {
                $table->string('estado')->default('Activo')->after('cosecha_estimada');
            }

            if (! Schema::hasColumn('cultivos', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('estado');
            }

            if (! Schema::hasColumn('cultivos', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('observaciones');
            }

            if (! Schema::hasColumn('cultivos', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });

        if (Schema::hasColumn('cultivos', 'categoria_cultivos_id')) {
            DB::statement('ALTER TABLE cultivos MODIFY categoria_cultivos_id BIGINT UNSIGNED NULL');
        }

        if (Schema::hasColumn('cultivos', 'ciclo_dias')) {
            DB::statement('ALTER TABLE cultivos MODIFY ciclo_dias INT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cultivos')) {
            return;
        }

        Schema::table('cultivos', function (Blueprint $table) {
            $columnsToDrop = [];

            foreach ([
                'updated_by',
                'created_by',
                'observaciones',
                'estado',
                'cosecha_estimada',
                'hectareas',
                'fecha_cosecha',
                'duracion_ciclo',
                'fecha_siembra',
                'unidad_medida',
                'ciclo',
                'variedad',
                'codigo',
                'lotes_id',
                'empresa_id',
            ] as $column) {
                if (Schema::hasColumn('cultivos', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};