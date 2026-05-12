<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles') || Schema::hasColumn('roles', 'estado')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('estado')->default(1)->after('nombre');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasColumn('roles', 'estado')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }

};
