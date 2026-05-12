<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sucursales') || Schema::hasColumn('sucursales', 'estado')) {
            return;
        }

        Schema::table('sucursales', function (Blueprint $table) {
            $table->boolean('estado')->default(1)->after('ubicacion');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sucursales') || !Schema::hasColumn('sucursales', 'estado')) {
            return;
        }

        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }

};
