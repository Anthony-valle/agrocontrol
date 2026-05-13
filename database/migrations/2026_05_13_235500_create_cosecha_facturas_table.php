<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cosecha_facturas')) {
            return;
        }

        Schema::create('cosecha_facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->unsignedBigInteger('empresa_consecutivo')->nullable();
            $table->foreignId('cosecha_id')->constrained('cosechas')->cascadeOnDelete();
            $table->string('numero_factura');
            $table->string('cliente')->nullable();
            $table->date('fecha_factura');
            $table->decimal('cantidad_vendida', 10, 2);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('total', 12, 2);
            $table->string('archivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->text('delete_reason')->nullable();

            $table->unique(['empresa_id', 'empresa_consecutivo'], 'cosecha_facturas_empresa_id_consecutivo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cosecha_facturas');
    }
};