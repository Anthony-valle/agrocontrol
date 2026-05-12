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
        Schema::create('cosechas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cultivo_id')->constrained('cultivos')->cascadeOnDelete();
            $table->date('fecha_cosecha');
            $table->decimal('cantidad_bruta', 15, 2);
            $table->decimal('cantidad_descarte', 15, 2)->default(0);
            $table->decimal('cantidad_neta', 15, 2);
            $table->string('unidad_medida', 20)->nullable(); 
            $table->string('motivo_descarte')->nullable();
            $table->text('observaciones')->nullable();
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
        Schema::dropIfExists('cosechas');
    }
};
