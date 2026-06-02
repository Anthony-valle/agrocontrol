<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlanesCultivoTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithEvents
{
    public function headings(): array
    {
        return [
            'cultivo_id',
            'cultivo_codigo',
            'cultivo_nombre',
            'fecha_plan',
            'semana',
            'categoria',
            'descripcion',
            'cantidad_estimada',
            'unidad_medida',
            'costo_unitario',
        ];
    }

    public function array(): array
    {
        return [
            [
                3,
                'CUL-0001',
                'Maiz Amarillo',
                '2026-04-21',
                0,
                'Mano de Obra',
                'Limpieza de terreno',
                3,
                'Jornal',
                400,
            ],
            [
                3,
                'CUL-0001',
                'Maiz Amarillo',
                '2026-04-21',
                0,
                'Preparacion de Suelo',
                'Arado mecanizado',
                1,
                'Servicio',
                1800,
            ],
            [
                3,
                'CUL-0001',
                'Maiz Amarillo',
                '2026-04-21',
                1,
                'Fertilizante',
                'Urea 46%',
                4,
                'Kg',
                590,
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '198754'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = count($this->array()) + 1;

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:J1');
                $sheet->getStyle("A1:J{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D8DEE4'],
                        ],
                    ],
                ]);
                $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:J1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}