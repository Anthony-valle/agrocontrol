<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labores', function (Blueprint $table) {
            $table->id();
            // --- ESTRUCTURA TIPO SAP ---
            $table->string('codigo')->unique(); // Ej: M-RIE-001
            $table->string('nombre');          // Ej: Riego por goteo
            
            // Jerarquía de actividades
            $table->string('actividad_secundaria'); // Ej: Mantenimiento tubería
            
            // Detalles de costos y unidades
            $table->string('unidad_medida');        // Ej: Jornal, Hora Máquina
            $table->decimal('costo_unitario', 12, 2); // Costo por unidad
            
            $table->text('observaciones')->nullable();
              // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labores');
    }
};