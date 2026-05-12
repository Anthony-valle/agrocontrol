<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('preparacion_suelo_actividades') || Schema::hasColumn('preparacion_suelo_actividades', 'deleted_at')) {
            return;
        }

        Schema::table('preparacion_suelo_actividades', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('preparacion_suelo_actividades') || !Schema::hasColumn('preparacion_suelo_actividades', 'deleted_at')) {
            return;
        }

        Schema::table('preparacion_suelo_actividades', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};