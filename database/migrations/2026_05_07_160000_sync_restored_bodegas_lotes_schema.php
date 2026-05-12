<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->syncBodegas();
        $this->syncLotes();
    }

    public function down(): void
    {
        // Migracion de compatibilidad: no elimina columnas restauradas para evitar perdida de datos.
    }

    private function syncBodegas(): void
    {
        if (!Schema::hasTable('bodegas')) {
            return;
        }

        Schema::table('bodegas', function (Blueprint $table) {
            if (!Schema::hasColumn('bodegas', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('bodegas', 'codigo')) {
                $table->string('codigo', 50)->nullable()->after('empresa_id');
            }

            if (!Schema::hasColumn('bodegas', 'responsable')) {
                $table->string('responsable', 100)->nullable()->after('nombre');
            }

            if (!Schema::hasColumn('bodegas', 'ubicacion')) {
                $table->string('ubicacion', 150)->nullable()->after('responsable');
            }

            if (!Schema::hasColumn('bodegas', 'estado')) {
                $table->boolean('estado')->default(1)->after('ubicacion');
            }

            if (!Schema::hasColumn('bodegas', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('sucursal_id');
            }

            if (!Schema::hasColumn('bodegas', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });

        if (Schema::hasColumn('bodegas', 'empresa_id') && Schema::hasColumn('bodegas', 'sucursal_id')) {
            DB::statement(
                'UPDATE bodegas b '
                . 'INNER JOIN sucursales s ON s.id = b.sucursal_id '
                . 'SET b.empresa_id = COALESCE(b.empresa_id, s.empresa_id) '
                . 'WHERE b.empresa_id IS NULL'
            );
        }
    }

    private function syncLotes(): void
    {
        if (!Schema::hasTable('lotes')) {
            return;
        }

        Schema::table('lotes', function (Blueprint $table) {
            if (!Schema::hasColumn('lotes', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('lotes', 'codigo')) {
                $table->string('codigo', 50)->nullable()->after('empresa_id');
            }

            if (!Schema::hasColumn('lotes', 'nombre')) {
                $table->string('nombre', 150)->nullable()->after('codigo');
            }

            if (!Schema::hasColumn('lotes', 'area')) {
                $table->decimal('area', 12, 2)->nullable()->after('nombre');
            }

            if (!Schema::hasColumn('lotes', 'poligono')) {
                $table->longText('poligono')->nullable()->after('area');
            }

            if (!Schema::hasColumn('lotes', 'estado')) {
                $table->boolean('estado')->default(1)->after('poligono');
            }

            if (!Schema::hasColumn('lotes', 'sucursal_id')) {
                $table->unsignedBigInteger('sucursal_id')->nullable()->after('estado');
            }

            if (!Schema::hasColumn('lotes', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('sucursal_id');
            }

            if (!Schema::hasColumn('lotes', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });

        if (Schema::hasColumn('lotes', 'nombre_lote') && Schema::hasColumn('lotes', 'nombre')) {
            DB::table('lotes')
                ->whereNull('nombre')
                ->update(['nombre' => DB::raw('nombre_lote')]);
        }

        if (Schema::hasColumn('lotes', 'tamaño') && Schema::hasColumn('lotes', 'area')) {
            DB::table('lotes')
                ->whereNull('area')
                ->update(['area' => DB::raw('`tamaño`')]);
        }

        if (Schema::hasColumn('lotes', 'sucursales_id') && Schema::hasColumn('lotes', 'sucursal_id')) {
            DB::table('lotes')
                ->whereNull('sucursal_id')
                ->update(['sucursal_id' => DB::raw('sucursales_id')]);
        }

        if (Schema::hasColumn('lotes', 'ubicacion') && Schema::hasColumn('lotes', 'poligono')) {
            DB::table('lotes')
                ->whereNull('poligono')
                ->whereNotNull('ubicacion')
                ->update(['poligono' => DB::raw('JSON_ARRAY()')]);
        }

        if (Schema::hasColumn('lotes', 'empresa_id') && Schema::hasColumn('lotes', 'sucursal_id')) {
            DB::statement(
                'UPDATE lotes l '
                . 'INNER JOIN sucursales s ON s.id = l.sucursal_id '
                . 'SET l.empresa_id = COALESCE(l.empresa_id, s.empresa_id) '
                . 'WHERE l.empresa_id IS NULL'
            );
        }
    }
};