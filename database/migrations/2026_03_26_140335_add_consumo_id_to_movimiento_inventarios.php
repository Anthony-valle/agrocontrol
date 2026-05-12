<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('movimiento_inventarios') || !Schema::hasColumn('movimiento_inventarios', 'insumo_id')) {
            return;
        }

        if (!Schema::hasTable('consumos') || Schema::hasColumn('movimiento_inventarios', 'consumo_id')) {
            return;
        }

        Schema::table('movimiento_inventarios', function (Blueprint $table) {
            $table->foreignId('consumo_id')
                ->nullable()
                ->after('insumo_id')
                ->constrained('consumos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('movimiento_inventarios') || !Schema::hasColumn('movimiento_inventarios', 'consumo_id')) {
            return;
        }

        Schema::table('movimiento_inventarios', function (Blueprint $table) {
            $table->dropForeign(['consumo_id']);
            $table->dropColumn('consumo_id');
        });
    }
};
