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
        Schema::create('insumo_salidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')->constrained('insumos');
            $table->foreignId('bodega_id')->constrained('bodegas');
            $table->decimal('cantidad', 10, 2);
            $table->string('motivo')->nullable();
            $table->date('fecha_salida');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insumo_salidas');
    }
};
