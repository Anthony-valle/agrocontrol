<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConsumosResumenSheet implements FromArray, ShouldAutoSize, WithDrawings, WithEvents, WithStyles, WithTitle
{
    public function __construct(private readonly Collection $registros)
    {
    }

    public function array(): array
    {
        $porCategoria = $this->registros
            ->groupBy('categoria')
            ->map(function (Collection $items, string $categoria) {
                return [
                    $categoria,
                    $items->count(),
                    (float) $items->sum('cantidad'),
                    (float) $items->sum('subtotal'),
                ];
            })
            ->values()
            ->all();

        return array_merge([
            ['Reporte de Consumos por Categoria'],
            ['Generado', now()->format('d/m/Y H:i')],
            ['Categoria', 'Registros', 'Cantidad Total', 'Subtotal'],
        ], $porCategoria, [
            ['TOTAL', $this->registros->count(), (float) $this->registros->sum('cantidad'), (float) $this->registros->sum('subtotal')],
        ]);
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function drawings(): array
    {
        $logoPath = public_path('NiceAdmin/assets/img/agrocontrol.png');

        if (! file_exists($logoPath)) {
            return [];
        }

        $drawing = new Drawing();
        $drawing->setName('Logo AgroControl');
        $drawing->setDescription('Logo AgroControl');
        $drawing->setPath($logoPath);
        $drawing->setHeight(54);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(8);
        $drawing->setOffsetY(6);

        return [$drawing];
    }

    public function styles(Worksheet $sheet): array
    {
        $totalRow = 4 + $this->registros->groupBy('categoria')->count();

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '16624F']],
            ],
            2 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '5B6470']],
            ],
            3 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '16624F'],
                ],
            ],
            $totalRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7F3EE'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalRow = 4 + $this->registros->groupBy('categoria')->count();

                $sheet->mergeCells('B1:D1');
                $sheet->mergeCells('B2:D2');
                $sheet->setCellValue('A1', '');
                $sheet->setCellValue('A2', '');
                $sheet->setCellValue('B1', 'Reporte de Consumos por Categoria');
                $sheet->setCellValue('B2', 'Generado: ' . now()->format('d/m/Y H:i'));
                $sheet->freezePane('A4');

                $sheet->getRowDimension(1)->setRowHeight(48);
                $sheet->getRowDimension(2)->setRowHeight(24);

                $sheet->getStyle("A3:D{$totalRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D9E2EC'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A1:D2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('B1:D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('B2:D2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("B4:D{$totalRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle("B4:D{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
