<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ordenes_compra')) {
            return;
        }

        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->foreignId('solicitud_compra_id')->constrained('solicitudes_compra')->cascadeOnDelete();
            $table->unsignedBigInteger('generado_por')->nullable();
            $table->string('codigo', 40)->unique();
            $table->string('proveedor', 160);
            $table->date('fecha_emision');
            $table->enum('estado', ['BORRADOR', 'ENVIADA', 'RECIBIDA', 'CANCELADA'])->default('BORRADOR');
            $table->decimal('total_estimado', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->json('detalle_items')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'estado']);
            $table->index('generado_por');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};