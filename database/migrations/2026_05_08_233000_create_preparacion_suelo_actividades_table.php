<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preparacion_suelo_actividades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('actividad_secundaria');
            $table->string('unidad_medida');
            $table->text('observaciones')->nullable();
            $table->boolean('estado')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $labores = DB::table('labores')
            ->select('empresa_id', 'codigo', 'nombre', 'actividad_secundaria', 'unidad_medida', 'observaciones', 'estado', 'created_by', 'updated_by', 'created_at', 'updated_at')
            ->orderBy('id')
            ->get();

        foreach ($labores as $labor) {
            DB::table('preparacion_suelo_actividades')->updateOrInsert(
                ['codigo' => $labor->codigo],
                [
                    'empresa_id' => $labor->empresa_id,
                    'nombre' => $labor->nombre,
                    'actividad_secundaria' => $labor->actividad_secundaria,
                    'unidad_medida' => $labor->unidad_medida,
                    'observaciones' => $labor->observaciones,
                    'estado' => isset($labor->estado) ? (int) $labor->estado : 1,
                    'created_by' => $labor->created_by,
                    'updated_by' => $labor->updated_by,
                    'created_at' => $labor->created_at,
                    'updated_at' => $labor->updated_at,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('preparacion_suelo_actividades');
    }
};