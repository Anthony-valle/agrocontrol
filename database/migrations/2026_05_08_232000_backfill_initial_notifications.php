<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('notificaciones')->exists()) {
            return;
        }

        $cultivo = DB::table('cultivos')
            ->select('id', 'empresa_id', 'nombre')
            ->orderBy('id')
            ->first();

        $user = DB::table('users')
            ->select('id')
            ->orderBy('id')
            ->first();

        if (!$user) {
            return;
        }

        DB::table('notificaciones')->insert([
            'empresa_id' => $cultivo->empresa_id ?? null,
            'cultivo_id' => $cultivo->id ?? null,
            'user_id' => $user->id,
            'mensaje' => $cultivo
                ? 'Centro de mensajería activado para el cultivo ' . $cultivo->nombre
                : 'Centro de mensajería activado correctamente.',
            'tipo' => 'auditoria',
            'leido' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('notificaciones')
            ->where('tipo', 'auditoria')
            ->where('mensaje', 'like', 'Centro de mensajería activado%')
            ->delete();
    }
};