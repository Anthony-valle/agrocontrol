<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labores', function (Blueprint $table) {
            if (!Schema::hasColumn('labores', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
                $table->index('empresa_id', 'labores_empresa_id_index');
            }

            if (!Schema::hasColumn('labores', 'estado')) {
                $table->boolean('estado')->default(true)->after('observaciones');
            }
        });

        DB::table('labores')->whereNull('estado')->update(['estado' => 1]);
    }

    public function down(): void
    {
        Schema::table('labores', function (Blueprint $table) {
            if (Schema::hasColumn('labores', 'estado')) {
                $table->dropColumn('estado');
            }

            if (Schema::hasColumn('labores', 'empresa_id')) {
                $table->dropIndex('labores_empresa_id_index');
                $table->dropColumn('empresa_id');
            }
        });
    }
};