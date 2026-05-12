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
        Schema::create('lote_fabricacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')->constrained('insumos');
            $table->foreignId('bodega_id')->constrained('bodegas');

            $table->string('numero_lote');
            $table->date('fecha_fabricacion')->nullable();
            $table->date('fecha_vencimiento')->nullable();

            $table->decimal('stock_actual', 10,2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lote_fabricacions');
    }
};
