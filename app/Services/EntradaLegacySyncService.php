<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EntradaLegacySyncService
{
    private array $columnasTablaCache = [];

    public function registrar(array $data): void
    {
        $this->registrarInsumoEntrada($data);
    }

    private function registrarInsumoEntrada(array $data): void
    {
        if (! Schema::hasTable('insumo_entradas')) {
            return;
        }

        $payload = [
            'insumo_id' => $data['insumo_id'] ?? null,
            'bodega_id' => $data['bodega_id'] ?? null,
            'tipo' => $data['tipo'] ?? 'compra',
            'cantida' => $data['cantidad'] ?? 0,
            'costo_unitario' => $data['costo_unitario'] ?? ($data['precio_unitario'] ?? 0),
            'factura' => $data['factura'] ?? null,
            'proveedor' => $data['proveedor'] ?? null,
            'fecha_ingreso' => $data['fecha_ingreso'] ?? now()->toDateString(),
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['updated_by'] ?? ($data['created_by'] ?? null),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = $this->filtrarColumnasPersistidas('insumo_entradas', $payload);

        if ($payload === []) {
            return;
        }

        DB::table('insumo_entradas')->insert($payload);
    }

    private function filtrarColumnasPersistidas(string $tabla, array $payload): array
    {
        return array_intersect_key($payload, array_flip($this->obtenerColumnasTabla($tabla)));
    }

    private function obtenerColumnasTabla(string $tabla): array
    {
        if (! isset($this->columnasTablaCache[$tabla])) {
            $this->columnasTablaCache[$tabla] = Schema::hasTable($tabla)
                ? Schema::getColumnListing($tabla)
                : [];
        }

        return $this->columnasTablaCache[$tabla];
    }
}