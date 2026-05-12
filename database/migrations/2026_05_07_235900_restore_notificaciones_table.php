<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notificaciones')) {
            return;
        }

        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->unsignedBigInteger('empresa_consecutivo')->nullable();
            $table->foreignId('cultivo_id')->constrained('cultivos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mensaje');
            $table->string('tipo')->default('consumo');
            $table->boolean('leido')->default(false);
            $table->timestamps();

            $table->unique(['empresa_id', 'empresa_consecutivo'], 'notificaciones_empresa_id_consecutivo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};