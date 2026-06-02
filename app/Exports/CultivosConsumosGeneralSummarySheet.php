<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class CultivosConsumosGeneralSummarySheet implements FromArray, ShouldAutoSize, WithEvents, WithStyles, WithTitle
{
    public function __construct(
        private readonly Collection $resumenCultivos,
        private readonly array $resumenGeneral,
    )
    {
    }

    public function array(): array
    {
        $blocks = $this->buildBlocks();

        $headerTop = ['Descripción/Actividad'];
        $headerBottom = [''];

        foreach ($blocks as $block) {
            $headerTop[] = $block['label'];
            $headerTop[] = '';
            $headerTop[] = '';
            $headerTop[] = '';
            array_push($headerBottom, 'Plan', 'Real', 'Desviación', 'Detalle');
        }

        $rows = [
            ['Resumen general de cultivos'],
            ['Generado', now()->format('d/m/Y H:i')],
            $this->buildMetaStripRow($blocks),
            $headerTop,
            $headerBottom,
        ];

        foreach ($this->resumenGeneral['filas'] as $fila) {
            $row = [$fila['categoria']];

            foreach ($blocks as $block) {
                array_push(
                    $row,
                    (float) $block['resolver']($fila, 'plan'),
                    (float) $block['resolver']($fila, 'real'),
                    (float) $block['resolver']($fila, 'desviacion'),
                    $block['detail_text']
                );
            }

            $rows[] = $row;
        }

        $totalRow = ['Total costo de producción'];

        foreach ($blocks as $block) {
            array_push(
                $totalRow,
                (float) $block['total_resolver']('plan'),
                (float) $block['total_resolver']('real'),
                (float) $block['total_resolver']('desviacion'),
                $block['detail_text']
            );
        }

        $rows[] = $totalRow;

        return $rows;
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function styles(Worksheet $sheet): array
    {
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
                    'startColor' => ['rgb' => '2F7A9A'],
                ],
            ],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F6B8F'],
                ],
            ],
            5 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2F7A9A'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $blocks = $this->buildBlocks();
                $lastColumnIndex = 1 + (count($blocks) * 4);
                $lastColumn = $this->columnLetter($lastColumnIndex);
                $lastRow = 6 + count($this->resumenGeneral['filas']);

                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("B2:C2");
                $sheet->freezePane('B6');

                $column = 2;
                foreach ($blocks as $block) {
                    $start = $this->columnLetter($column);
                    $end = $this->columnLetter($column + 3);
                    $sheet->mergeCells("{$start}3:{$end}3");
                    $sheet->mergeCells("{$start}4:{$end}4");
                    if ($block['sheet_name']) {
                        $sheet->getCell("{$start}4")->getHyperlink()->setUrl("#'{$block['sheet_name']}'!A1");
                        $sheet->getStyle("{$start}4")->getFont()->setUnderline(true);
                }
                    $column += 4;
                }

                $sheet->getStyle("A3:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '24313A'],
                        ],
                    ],
                ]);

                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A3:{$lastColumn}5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B6:{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("B6:{$lastColumn}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');

                foreach ($blocks as $index => $block) {
                    $planColumn = $this->columnLetter(2 + ($index * 4));
                    $realColumn = $this->columnLetter(3 + ($index * 4));
                    $deviationColumn = $this->columnLetter(4 + ($index * 4));

                    for ($row = 6; $row < $lastRow; $row++) {
                        $sheet->setCellValue("{$deviationColumn}{$row}", "={$realColumn}{$row}-{$planColumn}{$row}");
                    }

                    $sheet->setCellValue("{$planColumn}{$lastRow}", sprintf('=SUM(%s6:%s%d)', $planColumn, $planColumn, $lastRow - 1));
                    $sheet->setCellValue("{$realColumn}{$lastRow}", sprintf('=SUM(%s6:%s%d)', $realColumn, $realColumn, $lastRow - 1));
                    $sheet->setCellValue("{$deviationColumn}{$lastRow}", "={$realColumn}{$lastRow}-{$planColumn}{$lastRow}");
                }

                $column = 4;
                while ($column <= $lastColumnIndex) {
                    $deviationColumn = $this->columnLetter($column);
                    $sheet->getStyle("{$deviationColumn}6:{$deviationColumn}{$lastRow}")->getFont()->getColor()->setRGB('D11A2A');
                    $detailColumn = $this->columnLetter($column + 1);
                    if ($detailColumn <= $lastColumn) {
                        $sheet->getStyle("{$detailColumn}6:{$detailColumn}{$lastRow}")->getFont()->getColor()->setRGB('0563C1');
                        $sheet->getStyle("{$detailColumn}6:{$detailColumn}{$lastRow}")->getFont()->setUnderline(true);
                    }
                    $column += 4;
                }

                foreach ($blocks as $index => $block) {
                    if (! $block['sheet_name']) {
                        continue;
                    }

                    $detailColumn = $this->columnLetter(5 + ($index * 4));

                    for ($row = 6; $row <= $lastRow; $row++) {
                        $sheet->getCell("{$detailColumn}{$row}")->getHyperlink()->setUrl("#'{$block['sheet_name']}'!A1");
                    }
                }
            },
        ];
    }

    private function buildMetaStripRow(array $blocks): array
    {
        $row = [''];

        foreach ($blocks as $block) {
            $row[] = $block['meta'];
            $row[] = '';
            $row[] = '';
            $row[] = '';
        }

        return $row;
    }

    private function buildBlocks(): array
    {
        return collect($this->resumenGeneral['cultivos'] ?? [])->map(function (array $cultivo) {
            $cultivoId = (int) ($cultivo['id'] ?? 0);
            $meta = sprintf(
                'Lote:%s    Fecha Siembra:%s    Area: %s',
                (string) ($cultivo['lote'] ?? '-'),
                ! empty($cultivo['fecha_siembra']) ? Carbon::parse($cultivo['fecha_siembra'])->format('d/m/Y') : '-',
                $cultivo['hectareas'] !== null ? agro_number((float) $cultivo['hectareas'], 2) : '-'
            );

            return [
                'label' => $cultivo['nombre'] ?? ('Cultivo ' . $cultivoId),
                'meta' => $meta,
                'sheet_name' => $cultivo['sheet_name'] ?? null,
                'detail_text' => 'Ver detalle mensual',
                'resolver' => function (array $fila, string $field) use ($cultivoId) {
                    return (float) (($fila['cultivos'][$cultivoId][$field] ?? 0));
                },
                'total_resolver' => function (string $field) use ($cultivoId) {
                    return (float) (($this->resumenGeneral['totales'][$cultivoId][$field] ?? 0));
                },
            ];
        })->all();
    }

    private function columnLetter(int $index): string
    {
        $index = max(1, $index);
        $letter = '';

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - $mod - 1, 26);
        }

        return $letter;
    }
}