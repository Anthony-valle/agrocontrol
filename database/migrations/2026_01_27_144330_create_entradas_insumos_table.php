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
        Schema::create('entradas_insumos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('factura')->nullable();
            $table->text('observacion')->nullable();
            $table->unsignedMediumInteger('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entradas_insumos');
    }
};
