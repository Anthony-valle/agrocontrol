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

class ConsumosCategoriaSheet implements FromArray, ShouldAutoSize, WithDrawings, WithEvents, WithStyles, WithTitle
{
    public function __construct(
        private readonly string $categoria,
        private readonly Collection $items,
    ) {
    }

    public function array(): array
    {
        $filas = $this->items->map(function (array $item) {
            return [
                $item['fecha'] ?? '-',
                $item['lote'] ?? '-',
                $item['cultivo'] ?? '-',
                $item['codigo'] ?? '-',
                $item['insumo_concepto'] ?? '-',
                $item['lote_consumido'] ?? '-',
                $item['ingrediente_activo'] ?? '-',
                $item['cantidad'] ?? 0,
                $item['unidad'] ?? '-',
                $item['subtotal'] ?? 0,
            ];
        })->values()->all();

        return array_merge([
            ['Categoria', $this->categoria],
            ['Generado', now()->format('d/m/Y H:i')],
            ['Fecha', 'Lote', 'Cultivo', 'Codigo', 'Insumo / Concepto', 'Lote consumido', 'Ingrediente activo', 'Cantidad', 'Unidad', 'Subtotal'],
        ], $filas, [
            ['', '', '', '', '', '', '', '', 'TOTAL', (float) $this->items->sum('subtotal')],
        ]);
    }

    public function title(): string
    {
        $title = preg_replace('/[\\\\\/\?\*\[\]\:]/', ' ', $this->categoria) ?: 'Categoria';

        return mb_substr(trim($title), 0, 31);
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
        $totalRow = 4 + $this->items->count();

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
                    'startColor' => ['rgb' => 'FFF4D6'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalRow = 4 + $this->items->count();

                $sheet->mergeCells('B1:J1');
                $sheet->mergeCells('B2:J2');
                $sheet->setCellValue('A1', '');
                $sheet->setCellValue('A2', '');
                $sheet->setCellValue('B1', 'Reporte de Consumos - ' . $this->categoria);
                $sheet->setCellValue('B2', 'Generado: ' . now()->format('d/m/Y H:i'));
                $sheet->freezePane('A4');

                $sheet->getRowDimension(1)->setRowHeight(48);
                $sheet->getRowDimension(2)->setRowHeight(24);

                $sheet->getStyle("A3:J{$totalRow}")->applyFromArray([
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

                $sheet->getStyle('H4:J' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('H4:H' . $totalRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle('J4:J' . $totalRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle('A1:J2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('B1:J1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('B2:J2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            },
        ];
    }
}
