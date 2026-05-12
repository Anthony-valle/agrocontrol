<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notificaciones') || !Schema::hasColumn('notificaciones', 'cultivo_id')) {
            return;
        }

        $foreignKey = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'notificaciones')
            ->where('COLUMN_NAME', 'cultivo_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($foreignKey) {
            DB::statement(sprintf('ALTER TABLE `notificaciones` DROP FOREIGN KEY `%s`', $foreignKey));
        }

        DB::statement('ALTER TABLE `notificaciones` MODIFY `cultivo_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `notificaciones` ADD CONSTRAINT `notificaciones_cultivo_id_foreign` FOREIGN KEY (`cultivo_id`) REFERENCES `cultivos`(`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        if (!Schema::hasTable('notificaciones') || !Schema::hasColumn('notificaciones', 'cultivo_id')) {
            return;
        }

        $foreignKey = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'notificaciones')
            ->where('COLUMN_NAME', 'cultivo_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($foreignKey) {
            DB::statement(sprintf('ALTER TABLE `notificaciones` DROP FOREIGN KEY `%s`', $foreignKey));
        }

        $cultivoFallback = DB::table('cultivos')->orderBy('id')->value('id');
        if ($cultivoFallback) {
            DB::table('notificaciones')
                ->whereNull('cultivo_id')
                ->update(['cultivo_id' => $cultivoFallback]);

            DB::statement('ALTER TABLE `notificaciones` MODIFY `cultivo_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `notificaciones` ADD CONSTRAINT `notificaciones_cultivo_id_foreign` FOREIGN KEY (`cultivo_id`) REFERENCES `cultivos`(`id`) ON DELETE CASCADE');
        }
    }
};