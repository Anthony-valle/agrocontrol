<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $exists = DB::table('roles')
            ->whereRaw('LOWER(nombre) = ?', ['compra'])
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('roles')->insert([
            'nombre' => 'compra',
            'estado' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        DB::table('roles')
            ->whereRaw('LOWER(nombre) = ?', ['compra'])
            ->delete();
    }
};