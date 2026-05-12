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
        if (! Schema::hasTable('empresas')) {
            return;
        }

        Schema::table('empresas', function (Blueprint $table) {
            if (! Schema::hasColumn('empresas', 'rtn')) {
                $table->string('rtn')->nullable()->after('nombre');
            }

            if (! Schema::hasColumn('empresas', 'pais')) {
                $table->string('pais')->nullable()->after('email');
            }

            if (! Schema::hasColumn('empresas', 'departamento')) {
                $table->string('departamento')->nullable()->after('pais');
            }

            if (! Schema::hasColumn('empresas', 'tipo_empresa')) {
                $table->string('tipo_empresa')->nullable()->after('departamento');
            }
        });

        if (Schema::hasColumn('empresas', 'rtn') && Schema::hasColumn('empresas', 'nit')) {
            DB::statement('UPDATE empresas SET rtn = COALESCE(NULLIF(rtn, ""), nit)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('empresas')) {
            return;
        }

        Schema::table('empresas', function (Blueprint $table) {
            $columnasEliminar = [];

            foreach (['tipo_empresa', 'departamento', 'pais', 'rtn'] as $columna) {
                if (Schema::hasColumn('empresas', $columna)) {
                    $columnasEliminar[] = $columna;
                }
            }

            if ($columnasEliminar !== []) {
                $table->dropColumn($columnasEliminar);
            }
        });
    }
};