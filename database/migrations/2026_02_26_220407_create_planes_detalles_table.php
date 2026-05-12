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
        Schema::create('planes_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_cultivo_id')->constrained('planes_cultivos');
            $table->string('categoria');//fertilizacion, fitosanidad, Mano de obra
            $table->string('descripcion');
            $table->string('cantidad_estimada', 10, 2);
            $table->string('unidad_medida');//qq,litros,jornales
            $table->decimal('costo_unitario', 10, 2);
            $table->decimal('subtotal', 10 ,2);
             // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planes_detalles');
    }
};
