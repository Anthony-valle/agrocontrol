<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insumos', function (Blueprint $table) {
            if (! Schema::hasColumn('insumos', 'estado')) {
                $table->boolean('estado')->default(1);
            }

            if (! Schema::hasColumn('insumos', 'bloqueo_motivo')) {
                $table->text('bloqueo_motivo')->nullable();
            }

            if (! Schema::hasColumn('insumos', 'bloqueado_at')) {
                $table->timestamp('bloqueado_at')->nullable();
            }

            if (! Schema::hasColumn('insumos', 'bloqueado_por')) {
                $table->unsignedBigInteger('bloqueado_por')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('insumos', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('insumos', 'bloqueado_por')) {
                $columns[] = 'bloqueado_por';
            }

            if (Schema::hasColumn('insumos', 'bloqueado_at')) {
                $columns[] = 'bloqueado_at';
            }

            if (Schema::hasColumn('insumos', 'bloqueo_motivo')) {
                $columns[] = 'bloqueo_motivo';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};