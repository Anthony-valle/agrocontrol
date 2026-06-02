<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('solicitudes_compra')) {
            return;
        }

        Schema::create('solicitudes_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('solicitante_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('gestionado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recibido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('insumo_id')->nullable()->constrained('insumos')->nullOnDelete();
            $table->foreignId('bodega_destino_id')->nullable()->constrained('bodegas')->nullOnDelete();
            $table->foreignId('movimiento_inventario_id')->nullable()->constrained('movimiento_inventarios')->nullOnDelete();
            $table->unsignedBigInteger('factura_inventario_id')->nullable();
            $table->string('codigo', 30)->nullable();
            $table->string('departamento', 120)->nullable();
            $table->string('asunto', 150);
            $table->string('unidad', 60)->nullable();
            $table->decimal('cantidad', 12, 2)->default(0);
            $table->decimal('precio_estimado', 12, 2)->nullable();
            $table->string('prioridad', 20)->default('media');
            $table->string('estado', 30)->default('pendiente_aprobacion');
            $table->text('descripcion')->nullable();
            $table->text('observaciones_compra')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->date('fecha_requerida')->nullable();
            $table->timestamp('aprobado_en')->nullable();
            $table->timestamp('gestionado_en')->nullable();
            $table->timestamp('rechazado_en')->nullable();
            $table->timestamp('recibido_en')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'estado']);
            $table->index(['solicitante_id', 'created_at']);
            $table->index(['gestionado_por', 'estado']);
            $table->index(['aprobado_por', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_compra');
    }
};