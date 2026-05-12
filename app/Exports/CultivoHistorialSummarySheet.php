<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class CultivoHistorialSummarySheet implements FromArray, WithTitle
{
    protected $cultivo;

    public function __construct($cultivo)
    {
        $this->cultivo = $cultivo;
    }

    public function array(): array
    {
        $consumos = $this->cultivo->consumos->sortByDesc('fecha_consumo');
        $totalConsumo = $consumos->sum('total');
        $totalConsumos = $consumos->count();

        $categoryTotals = $consumos->flatMap(function ($consumo) {
            return $consumo->detalles->map(function ($detalle) {
                return [
                    'categoria' => $detalle->categoria,
                    'cantidad' => $detalle->cantidad,
                    'subtotal' => $detalle->subtotal,
                ];
            });
        })->groupBy('categoria')->map(function ($items) {
            return [
                'categoria' => $items->first()['categoria'],
                'cantidad' => $items->sum('cantidad'),
                'subtotal' => $items->sum('subtotal'),
            ];
        })->values();

        $rows = [
            ['Historial de Consumo del Cultivo'],
            ['Cultivo', 'Código', 'Estado', 'Unidad de Medida', 'Consumos Registrados', 'Costo Total'],
            [
                $this->cultivo->nombre,
                $this->cultivo->codigo,
                $this->cultivo->estado,
                $this->cultivo->unidad_medida ?? 'N/A',
                $totalConsumos,
                $totalConsumo,
            ],
            [],
            ['Resumen por Categoría'],
            ['Categoría', 'Cantidad', 'Costo'],
        ];

        foreach ($categoryTotals as $total) {
            $rows[] = [
                $total['categoria'],
                $total['cantidad'],
                $total['subtotal'],
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Resumen';
    }
}
