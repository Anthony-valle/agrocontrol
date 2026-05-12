<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('factura_inventarios') || Schema::hasColumn('factura_inventarios', 'deleted_at')) {
            return;
        }

        Schema::table('factura_inventarios', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('factura_inventarios') || !Schema::hasColumn('factura_inventarios', 'deleted_at')) {
            return;
        }

        Schema::table('factura_inventarios', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};