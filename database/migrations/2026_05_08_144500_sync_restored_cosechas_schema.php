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
        if (! Schema::hasTable('cosechas')) {
            return;
        }

        Schema::table('cosechas', function (Blueprint $table) {
            if (! Schema::hasColumn('cosechas', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('cosechas', 'descarte')) {
                $table->decimal('descarte', 15, 2)->default(0)->after('cantidad_bruta');
            }

            if (! Schema::hasColumn('cosechas', 'cantidad_disponible')) {
                $table->decimal('cantidad_disponible', 15, 2)->default(0)->after('cantidad_neta');
            }

            if (! Schema::hasColumn('cosechas', 'precio_venta_unitario')) {
                $table->decimal('precio_venta_unitario', 15, 2)->nullable()->after('cantidad_disponible');
            }
        });

        if (Schema::hasColumn('cosechas', 'cantidad_descarte') && Schema::hasColumn('cosechas', 'descarte')) {
            DB::table('cosechas')
                ->whereNotNull('cantidad_descarte')
                ->update(['descarte' => DB::raw('cantidad_descarte')]);
        }

        if (Schema::hasColumn('cosechas', 'empresa_id') && Schema::hasColumn('cosechas', 'cultivo_id') && Schema::hasColumn('cultivos', 'empresa_id')) {
            DB::statement('UPDATE cosechas c INNER JOIN cultivos cu ON cu.id = c.cultivo_id SET c.empresa_id = COALESCE(c.empresa_id, cu.empresa_id)');
        }

        if (Schema::hasColumn('cosechas', 'cantidad_neta') && Schema::hasColumn('cosechas', 'cantidad_disponible')) {
            DB::table('cosechas')
                ->where(function ($query) {
                    $query->whereNull('cantidad_disponible')
                        ->orWhere('cantidad_disponible', 0);
                })
                ->update(['cantidad_disponible' => DB::raw('cantidad_neta')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('cosechas')) {
            return;
        }

        Schema::table('cosechas', function (Blueprint $table) {
            if (Schema::hasColumn('cosechas', 'precio_venta_unitario')) {
                $table->dropColumn('precio_venta_unitario');
            }

            if (Schema::hasColumn('cosechas', 'cantidad_disponible')) {
                $table->dropColumn('cantidad_disponible');
            }

            if (Schema::hasColumn('cosechas', 'descarte')) {
                $table->dropColumn('descarte');
            }

            if (Schema::hasColumn('cosechas', 'empresa_id')) {
                $table->dropColumn('empresa_id');
            }
        });
    }
};