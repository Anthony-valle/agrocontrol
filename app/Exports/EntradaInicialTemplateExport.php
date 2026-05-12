<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EntradaInicialTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'codigo',
            'nombre',
            'ingrediente_activo',
            'categoria_nombre',
            'Unidad medida base',
            'stock_minimo',
            'estado',
            'bodega_id',
            'numero_lote',
            'stock_inicial',
            'costo_promedio',
        ];
    }

    public function array(): array
    {
        return [
            [
                '4000680',
                'NATIVO 75 WG',
                'TEBUCONAZOLE + TRIFLOXYSTROBIN WG 75',
                'Fitosanitario',
                'KG',
                5,
                1,
                1,
                'LG64000821',
                0.010,
                4892.930,
            ],
            [
                '4000690',
                'MAP 12-61-0',
                'MAP 12-61-0',
                'Fertilizante',
                'KG',
                5,
                1,
                1,
                '2026',
                4.706,
                40.650,
            ],
            [
                '4000690',
                'MAP 12-61-0',
                'MAP 12-61-0',
                'Fertilizante',
                'KG',
                5,
                1,
                1,
                '39SAC2309',
                276.836,
                40.650,
            ],
        ];
    }
}