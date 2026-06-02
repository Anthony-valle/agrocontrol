<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsumosTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'consumo_referencia',
            'cultivo_id',
            'cultivo_codigo',
            'cultivo_nombre',
            'fecha_consumo',
            'aplicar_consumo_real_bodega',
            'insumo_codigo',
            'descripcion_consumo',
            'cantidad',
            'unidad_medida',
            'precio_unitario',
            'subtotal',
            'bodega_id',
            'bodega_nombre',
            'lote_consumo',
        ];
    }

    public function array(): array
    {
        return [
            [
                'CONS-HIST-001',
                3,
                '',
                '',
                '2025-02-10',
                'NO',
                'INS-001',
                'Urea aplicada antes del sistema',
                4.250,
                'KG',
                590.125,
                '=I2*K2',
                '',
                '',
                '',
            ],
            [
                'CONS-HIST-002',
                '',
                'CUL-0007',
                '',
                '2025-03-15',
                'SI',
                'INS-014',
                'Aplicacion real tomada de bodega',
                2.750,
                'KG',
                837.990,
                '=I3*K3',
                3,
                'Bodega Insumos',
                'LOT-PIT-014',
            ],
            [
                'CONS-HIST-002',
                '',
                'CUL-0007',
                '',
                '2025-03-15',
                'SI',
                'INS-021',
                'Segunda linea del mismo consumo',
                1.500,
                'KG',
                158.940,
                '=I4*K4',
                3,
                'Bodega Insumos',
                'LOT-PIT-015',
            ],
        ];
    }
}