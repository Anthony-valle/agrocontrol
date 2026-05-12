<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('labores', 'empresa_id')) {
            return;
        }

        DB::statement(
            'UPDATE labores l
            INNER JOIN users u ON u.id = l.created_by
            INNER JOIN sucursales s ON s.id = u.sucursal_id
            SET l.empresa_id = s.empresa_id
            WHERE l.empresa_id IS NULL'
        );
    }

    public function down(): void
    {
    }
};