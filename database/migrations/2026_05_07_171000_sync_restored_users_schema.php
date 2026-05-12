<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nombre_completo')) {
                $table->string('nombre_completo')->nullable()->after('id');
            }

            if (!Schema::hasColumn('users', 'usuario')) {
                $table->string('usuario', 50)->nullable()->unique()->after('nombre_completo');
            }

            if (!Schema::hasColumn('users', 'estado')) {
                $table->boolean('estado')->default(1)->after('usuario');
            }

            if (!Schema::hasColumn('users', 'rol_id')) {
                $table->unsignedBigInteger('rol_id')->nullable()->after('password');
            }

            if (!Schema::hasColumn('users', 'sucursal_id')) {
                $table->unsignedBigInteger('sucursal_id')->nullable()->after('rol_id');
            }

            if (!Schema::hasColumn('users', 'imagen_usuario')) {
                $table->string('imagen_usuario')->nullable()->after('sucursal_id');
            }

            if (!Schema::hasColumn('users', 'access_permissions')) {
                $table->text('access_permissions')->nullable()->after('imagen_usuario');
            }

            if (!Schema::hasColumn('users', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('access_permissions');
            }

            if (!Schema::hasColumn('users', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });

        if (Schema::hasColumn('users', 'access_permissions')) {
            DB::table('users')
                ->whereNull('access_permissions')
                ->update(['access_permissions' => json_encode([])]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];

            foreach (['updated_by', 'created_by', 'access_permissions', 'imagen_usuario', 'sucursal_id', 'rol_id', 'estado', 'usuario', 'nombre_completo'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
