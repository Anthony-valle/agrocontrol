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
        Schema::create('insumos', function (Blueprint $table) {
            //Identificador
            $table->id();
            //campos de texto(varchar)
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('ingredientes_activo');
            $table->string('unidad_medida');
            $table->decimal('costo_estimado', 10,2)->default(0);
            //Fecha
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};
