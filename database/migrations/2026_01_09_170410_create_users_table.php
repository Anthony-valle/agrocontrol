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
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
        $table->id();

        $table->string('nombre_completo')->nullable();
        $table->string('usuario')->unique();
        $table->string('password');
        $table->string('rol')->nullable();
        $table->string('sucursal')->nullable();

        $table->boolean('estado')->default(true);

        $table->unsignedBigInteger('rol_id')->nullable();

        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
