<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimiento_inventarios', function (Blueprint $table) {
            $table->decimal('precio_unitario', 12, 2)->default(0)->after('cantidad');
        });
    }

    public function down(): void
    {
        Schema::table('movimiento_inventarios', function (Blueprint $table) {
            $table->dropColumn('precio_unitario');
        });
    }
};