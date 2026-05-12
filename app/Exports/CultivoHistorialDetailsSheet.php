<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CultivoHistorialDetailsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $cultivo;

    public function __construct($cultivo)
    {
        $this->cultivo = $cultivo;
    }

    public function collection(): Collection
    {
        return $this->cultivo->consumos->sortByDesc('fecha_consumo')->flatMap(function ($consumo) {
            return $consumo->detalles->map(function ($detalle) use ($consumo) {
                return [
                    'consumo_id' => $consumo->id,
                    'fecha_consumo' => $consumo->fecha_consumo,
                    'semana' => \Carbon\Carbon::parse($consumo->fecha_consumo)->weekOfYear,
                    'categoria' => $detalle->categoria,
                    'descripcion' => $detalle->descripcion,
                    'cantidad' => $detalle->cantidad,
                    'unidad_medida' => $detalle->unidad_medida,
                    'subtotal' => $detalle->subtotal,
                ];
            });
        })->values();
    }

    public function headings(): array
    {
        return [
            'Consumo ID',
            'Fecha Consumo',
            'Semana',
            'Categoría',
            'Descripción',
            'Cantidad',
            'Unidad de Medida',
            'Subtotal',
        ];
    }

    public function title(): string
    {
        return 'Detalle';
    }
}
