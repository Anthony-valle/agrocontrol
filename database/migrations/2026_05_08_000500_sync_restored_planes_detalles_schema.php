<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('planes_detalles')) {
            Schema::create('planes_detalles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_cultivo_id')->constrained('planes_cultivos')->cascadeOnDelete();
                $table->unsignedInteger('semana')->default(1);
                $table->string('categoria', 50);
                $table->string('descripcion', 150);
                $table->decimal('cantidad_estimada', 12, 2);
                $table->string('unidad_medida', 20);
                $table->decimal('costo_unitario', 12, 2);
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('planes_detalles', 'semana')) {
            Schema::table('planes_detalles', function (Blueprint $table) {
                $table->unsignedInteger('semana')->default(1)->after('plan_cultivo_id');
            });
        }

        DB::statement("ALTER TABLE planes_detalles MODIFY categoria VARCHAR(50) NOT NULL");
        DB::statement("ALTER TABLE planes_detalles MODIFY descripcion VARCHAR(150) NOT NULL");
        DB::statement("ALTER TABLE planes_detalles MODIFY cantidad_estimada DECIMAL(12,2) NOT NULL");
        DB::statement("ALTER TABLE planes_detalles MODIFY unidad_medida VARCHAR(20) NOT NULL");
        DB::statement("ALTER TABLE planes_detalles MODIFY costo_unitario DECIMAL(12,2) NOT NULL");
        DB::statement("ALTER TABLE planes_detalles MODIFY subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00");
    }

    public function down(): void
    {
        if (! Schema::hasTable('planes_detalles')) {
            return;
        }

        DB::statement("ALTER TABLE planes_detalles MODIFY categoria VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE planes_detalles MODIFY descripcion VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE planes_detalles MODIFY cantidad_estimada VARCHAR(10) NOT NULL");
        DB::statement("ALTER TABLE planes_detalles MODIFY unidad_medida VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE planes_detalles MODIFY costo_unitario DECIMAL(10,2) NOT NULL");
        DB::statement("ALTER TABLE planes_detalles MODIFY subtotal DECIMAL(10,2) NOT NULL");

        if (Schema::hasColumn('planes_detalles', 'semana')) {
            Schema::table('planes_detalles', function (Blueprint $table) {
                $table->dropColumn('semana');
            });
        }
    }
};