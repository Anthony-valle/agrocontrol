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
        if (Schema::hasTable('sucursales')) {
            return;
        }

        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('empresa_nombre');
            $table->string('nombre');
            $table->string('ubicacion')->nullable();
            $table->boolean('estado')->default(1);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};
