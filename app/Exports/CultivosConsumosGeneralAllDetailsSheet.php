<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CultivosConsumosGeneralAllDetailsSheet implements FromArray, ShouldAutoSize, WithEvents, WithStyles, WithTitle
{
    public function __construct(
        private readonly Collection $detallesGenerales,
        private readonly string $sheetName = 'Detalle general',
        private readonly string $titleText = 'Detalle general consolidado de cultivos',
    ) {
    }

    public function array(): array
    {
        $rows = [
            [$this->titleText],
            ['Generado', now()->format('d/m/Y H:i')],
            [
                'Cultivo',
                'Codigo cultivo',
                'Lote cultivo',
                'Fecha',
                'Consumo',
                'Estado',
                'Categoria',
                'Codigo',
                'Insumo',
                'Bodega',
                'Lote',
                'Descripcion',
                'Cantidad',
                'Unidad',
                'Costo unitario',
                'Subtotal',
            ],
        ];

        foreach ($this->detallesGenerales as $detalle) {
            $rows[] = [
                $detalle['cultivo'],
                $detalle['cultivo_codigo'],
                $detalle['lote_cultivo'],
                $detalle['fecha'] !== '' ? $detalle['fecha'] : null,
                $detalle['consumo_id'],
                $detalle['estado'],
                $detalle['categoria'],
                $detalle['codigo'],
                $detalle['insumo'],
                $detalle['bodega'],
                $detalle['lote'],
                $detalle['descripcion'],
                (float) $detalle['cantidad'],
                $detalle['unidad_medida'],
                (float) $detalle['costo_unitario'],
                (float) $detalle['subtotal'],
            ];
        }

        $rows[] = [
            'Totales', '', '', '', '', '', '', '', '', '', '', '',
            null,
            '',
            null,
            null,
        ];

        return $rows;
    }

    public function title(): string
    {
        return mb_substr($this->sheetName, 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = 3 + $this->detallesGenerales->count() + 1;

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1F6B8F']],
            ],
            2 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '5B6470']],
            ],
            3 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F6B8F'],
                ],
            ],
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F7FAF9'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = 'P';
                $dataStartRow = 4;
                $totalRow = 3 + $this->detallesGenerales->count() + 1;

                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("B2:C2");
                $sheet->setCellValue('A2', 'Generado');
                $sheet->freezePane('A4');

                $sheet->getStyle("A3:{$lastColumn}{$totalRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '24313A'],
                        ],
                    ],
                ]);

                $sheet->getStyle("A1:{$lastColumn}{$totalRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("M{$dataStartRow}:M{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("O{$dataStartRow}:P{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("M{$dataStartRow}:M{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("O{$dataStartRow}:P{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("D{$dataStartRow}:D{$totalRow}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                if ($totalRow >= $dataStartRow) {
                    $sheet->setCellValue("M{$totalRow}", sprintf('=SUM(M%d:M%d)', $dataStartRow, max($dataStartRow, $totalRow - 1)));
                    $sheet->setCellValue("O{$totalRow}", sprintf('=SUM(O%d:O%d)', $dataStartRow, max($dataStartRow, $totalRow - 1)));
                    $sheet->setCellValue("P{$totalRow}", sprintf('=SUM(P%d:P%d)', $dataStartRow, max($dataStartRow, $totalRow - 1)));
                }
            },
        ];
    }
}