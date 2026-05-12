<?php

namespace App\Exports;

use App\Models\Categorias;
use App\Models\Insumo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InventarioReportExport implements FromArray, ShouldAutoSize
{
    public function __construct(private readonly Collection $inventarios)
    {
    }

    public function array(): array
    {
        $totalValor = 0;

        $filas = $this->inventarios->map(function ($item) use (&$totalValor) {
            $stock = (float) ($item->stock_actual ?? 0);
            $costo = (float) ($item->costo_promedio ?? 0);
            $valor = $stock * $costo;
            $totalValor += $valor;

            return [
                $item->insumo->codigo ?? '-',
                $item->insumo->nombre ?? '-',
                $this->resolverNombreCategoria($item->insumo),
                $item->bodega->nombre ?? '-',
                $item->bodega->sucursal->nombre ?? '-',
                $item->numero_lote ?: '-',
                $stock,
                $item->insumo->unidad_medida ?? '-',
                $costo,
                $valor,
                $item->fecha_fabricacion ? Carbon::parse($item->fecha_fabricacion)->format('d/m/Y') : '-',
                $item->fecha_vencimiento ? Carbon::parse($item->fecha_vencimiento)->format('d/m/Y') : '-',
            ];
        })->values()->all();

        return array_merge([
            ['Reporte de Inventario'],
            ['Generado', now()->format('d/m/Y H:i')],
            [],
            [
                'Codigo',
                'Insumo',
                'Categoria',
                'Bodega',
                'Sucursal',
                'Lote',
                'Stock',
                'Unidad',
                'Costo Promedio',
                'Valor Estimado',
                'F. Fabricacion',
                'F. Vencimiento',
            ],
        ], $filas, [[
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            'VALOR TOTAL',
            $totalValor,
            '',
            '',
        ]]);
    }

    private function resolverNombreCategoria(?Insumo $insumo): string
    {
        if (! $insumo) {
            return 'Sin categoria';
        }

        if (Schema::hasColumn('insumos', 'categoria_nombre') && ! empty($insumo->categoria_nombre)) {
            return (string) $insumo->categoria_nombre;
        }

        if (Schema::hasColumn('insumos', 'categoria_id') && ! empty($insumo->categoria_id)) {
            $categoriaNombre = DB::table('categorias')
                ->where('id', $insumo->categoria_id)
                ->value('nombre');

            if (! empty($categoriaNombre)) {
                return (string) $categoriaNombre;
            }

            $categoria = Categorias::query()->find($insumo->categoria_id);

            if ($categoria && ! empty($categoria->nombre)) {
                return (string) $categoria->nombre;
            }
        }

        return 'Sin categoria';
    }
}