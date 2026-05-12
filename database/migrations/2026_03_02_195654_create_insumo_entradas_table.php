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
        Schema::create('insumo_entradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')->constrained('insumos');
            $table->foreignId('bodega_id')->constrained('bodegas');
            $table->enum('tipo', ['inventario_inicial','compra','ajuste'])->default('compra');
            $table->decimal('cantida', 10, 2)->nullable();
            $table->decimal('costo_unitario', 10, 2)->nullable();
            $table->string('factura')->nullable();
            $table->string('proveedor')->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insumo_entradas');
    }
};
