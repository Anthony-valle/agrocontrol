<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class SchemaCache
{
    private static array $tables = [];

    private static array $columns = [];

    public static function hasTable(string $table): bool
    {
        return self::$tables[$table] ??= Schema::hasTable($table);
    }

    public static function columns(string $table): array
    {
        if (! array_key_exists($table, self::$columns)) {
            self::$columns[$table] = self::hasTable($table)
                ? Schema::getColumnListing($table)
                : [];
        }

        return self::$columns[$table];
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return in_array($column, self::columns($table), true);
    }
}