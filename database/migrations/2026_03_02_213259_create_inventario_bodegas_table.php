<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventario_bodegas', function (Blueprint $table) {
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
            $table->foreignId('bodega_id')->constrained('bodegas')->cascadeOnDelete();
            $table->decimal('stock_actual', 10, 2)->default(0);
            $table->decimal('costo_promedio', 10, 2)->default(0);
            $table->unique(['insumo_id','bodega_id']); // evita duplicados
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_bodegas');
    }
};
