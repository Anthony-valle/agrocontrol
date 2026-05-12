<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('movimiento_inventarios')) {
            return;
        }

        Schema::create('movimiento_inventarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos');
            $table->foreignId('bodega_origen_id')->nullable()->constrained('bodegas')->nullOnDelete();
            $table->foreignId('bodega_destino_id')->nullable()->constrained('bodegas')->nullOnDelete();
            $table->enum('tipo', ['ENTRADA', 'SALIDA', 'TRASLADO', 'CONSUMO', 'AJUSTE'])->default('ENTRADA');
            $table->decimal('cantidad', 12, 2)->default(0);
            $table->decimal('stock_anterior', 12, 2)->nullable();
            $table->decimal('stock_actual', 12, 2)->nullable();
            $table->decimal('costo_unitario', 12, 2)->default(0);
            $table->text('descripcion')->nullable();
            $table->string('referencia', 100)->nullable();
            $table->string('numero_lote', 100)->nullable();
            $table->date('fecha_fabricacion')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimiento_inventarios');
    }
};
