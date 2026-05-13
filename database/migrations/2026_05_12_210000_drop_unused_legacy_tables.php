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
        Schema::dropIfExists('detalle_entrada_insumos');
        Schema::dropIfExists('entradas_insumos');
        Schema::dropIfExists('ordenes_compras');
        Schema::dropIfExists('proveedores');
        Schema::dropIfExists('inventarios');
        Schema::dropIfExists('siembras');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('entradas_insumos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('factura')->nullable();
            $table->text('observacion')->nullable();
            $table->unsignedMediumInteger('created_by');
            $table->timestamps();
        });

        Schema::create('detalle_entrada_insumos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('entrada_id');
            $table->unsignedBigInteger('insumo_id');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bodega_id')->constrained('bodegas');
            $table->foreignId('insumos_id')->constrained('insumos');
            $table->decimal('stock', 10, 2);
            $table->timestamps();
        });

        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('ordenes_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->date('fecha');
            $table->enum('estado', ['PENDIENTE', 'APROBADO', 'CANCELADA'])->default('PENDIENTE');
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('siembras', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};