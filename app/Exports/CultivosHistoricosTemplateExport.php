<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CultivosHistoricosTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'codigo',
            'nombre',
            'lote_id',
            'lote_nombre',
            'variedad',
            'ciclo',
            'fecha_siembra',
            'duracion_ciclo',
            'hectareas',
            'cosecha_estimada',
            'unidad_medida',
            'estado',
            'observaciones',
            'fecha_consumo',
            'aplicar_consumo_real_bodega',
            'insumo_codigo',
            'insumo_nombre',
            'categoria_consumo',
            'descripcion_consumo',
            'cantidad_por_ha',
            'unidad_consumo',
            'costo_unitario_consumo',
            'bodega_id',
            'bodega_nombre',
            'lote_consumo',
        ];
    }

    public function array(): array
    {
        return [
            [
                'CUL-HIST-001',
                'Maiz Historico',
                1,
                '',
                'Hibrido A',
                'Primera',
                '2025-01-15',
                130,
                2.500,
                4500.000,
                'kg',
                'Cerrado',
                'Cultivo cargado desde historial',
                '2025-02-10',
                'NO',
                'INS-001',
                'Urea 46%',
                'Fertilizante',
                'Urea aplicada antes del sistema',
                4.250,
                'kg',
                590.125,
                '',
                '',
                '',
            ],
            [
                'CUL-HIST-002',
                'Pitahaya Historica',
                '',
                'Lote Central',
                'Roja',
                'Ciclo 1',
                '2024-05-20',
                365,
                1.250,
                3200.500,
                'kg',
                'Cerrado',
                'Consumo si debe bajar inventario',
                '2024-06-15',
                'SI',
                'INS-014',
                'Sulfato de Potasio',
                'Fertilizante',
                'Aplicacion real tomada de bodega',
                2.750,
                'kg',
                837.990,
                3,
                'Bodega Insumos',
                'LOT-PIT-014',
            ],
        ];
    }
}