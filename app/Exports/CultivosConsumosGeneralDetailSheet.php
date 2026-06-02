<?php

namespace App\Exports;

use App\Models\Cultivo;
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

class CultivosConsumosGeneralDetailSheet implements FromArray, ShouldAutoSize, WithEvents, WithStyles, WithTitle
{
    public function __construct(
        private readonly string $sheetName,
        private readonly ?string $generalDetailSheetName,
        private readonly ?Cultivo $cultivoSeleccionado,
        private readonly array $meses,
        private readonly Collection $filas,
        private readonly array $totales,
    ) {
    }

    public function array(): array
    {
        $encabezadoSuperior = ['Descripción'];
        $encabezadoInferior = ['Operación'];

        foreach ($this->meses as $mes) {
            $encabezadoSuperior[] = $mes['label'];
            $encabezadoSuperior[] = '';
            $encabezadoSuperior[] = '';
            array_push($encabezadoInferior, 'Plan', 'Real', 'Desviación');
        }

        array_push($encabezadoSuperior, 'Totales', '', '', 'Desviación plan/real (%)');
        array_push($encabezadoInferior, 'Plan', 'Real', 'Desviación', '');

        $detalle = $this->filas->map(function (array $fila) {
            $row = [$fila['categoria']];

            foreach ($this->meses as $mes) {
                $datosMes = $fila['meses'][$mes['key']] ?? ['plan' => 0, 'real' => 0, 'desviacion' => 0];
                array_push($row, (float) $datosMes['plan'], (float) $datosMes['real'], (float) $datosMes['desviacion']);
            }

            array_push(
                $row,
                (float) $fila['total_plan'],
                (float) $fila['total_real'],
                (float) $fila['total_desviacion'],
                $fila['porcentaje'] !== null ? (float) $fila['porcentaje'] : null,
            );

            return $row;
        })->all();

        $totalRow = ['Total costo de producción'];

        foreach ($this->meses as $mes) {
            $datosMes = $this->totales['meses'][$mes['key']] ?? ['plan' => 0, 'real' => 0, 'desviacion' => 0];
            array_push($totalRow, (float) $datosMes['plan'], (float) $datosMes['real'], (float) $datosMes['desviacion']);
        }

        array_push(
            $totalRow,
            (float) ($this->totales['plan'] ?? 0),
            (float) ($this->totales['real'] ?? 0),
            (float) ($this->totales['desviacion'] ?? 0),
            $this->totales['porcentaje'] ?? null,
        );

        $titulo = $this->cultivoSeleccionado
            ? 'Detalle del cultivo: ' . $this->cultivoSeleccionado->nombre
            : 'Detalle consolidado de consumos';

        return array_merge([
            [$titulo],
            ['Generado', now()->format('d/m/Y H:i')],
            $encabezadoSuperior,
            $encabezadoInferior,
        ], $detalle, [$totalRow]);
    }

    public function title(): string
    {
        return mb_substr($this->sheetName, 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $this->columnLetter(1 + (count($this->meses) * 3) + 4);
        $lastRow = 4 + $this->filas->count() + 1;

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
            4 => [
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
                $lastColumn = $this->columnLetter(1 + (count($this->meses) * 3) + 4);
                $preLastColumn = $this->columnLetter(max(2, (1 + (count($this->meses) * 3) + 3)));
                $lastRow = 4 + $this->filas->count() + 1;
                $dataStartRow = 5;
                $totalStartColumn = 2 + (count($this->meses) * 3);
                $totalPlanColumn = $this->columnLetter($totalStartColumn);
                $totalRealColumn = $this->columnLetter($totalStartColumn + 1);
                $totalDeviationColumn = $this->columnLetter($totalStartColumn + 2);
                $percentageColumn = $this->columnLetter($totalStartColumn + 3);

                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->setCellValue('A2', 'Generado');

                if ($this->generalDetailSheetName) {
                    $sheet->mergeCells("B2:{$preLastColumn}2");
                    $sheet->setCellValue("{$lastColumn}2", 'Ver detalle general');
                    $sheet->getCell("{$lastColumn}2")->getHyperlink()->setUrl("#'{$this->generalDetailSheetName}'!A1");
                    $sheet->getStyle("{$lastColumn}2")->getFont()->setUnderline(true);
                    $sheet->getStyle("{$lastColumn}2")->getFont()->getColor()->setRGB('0563C1');
                    $sheet->getStyle("{$lastColumn}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                } else {
                    $sheet->mergeCells("B2:{$lastColumn}2");
                }

                $sheet->mergeCells('A3:A4');

                $column = 2;
                foreach ($this->meses as $mes) {
                    $start = $this->columnLetter($column);
                    $end = $this->columnLetter($column + 2);
                    $sheet->mergeCells("{$start}3:{$end}3");
                    $column += 3;
                }

                $totalesStart = $this->columnLetter($column);
                $totalesEnd = $this->columnLetter($column + 2);
                $sheet->mergeCells("{$totalesStart}3:{$totalesEnd}3");
                $porcentajeColumn = $this->columnLetter($column + 3);
                $sheet->mergeCells("{$porcentajeColumn}3:{$porcentajeColumn}4");
                $sheet->freezePane('B5');

                $sheet->getStyle("A3:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '24313A'],
                        ],
                    ],
                ]);

                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A3:{$lastColumn}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B5:{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("B5:{$lastColumn}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("{$percentageColumn}{$dataStartRow}:{$percentageColumn}{$lastRow}")->getNumberFormat()->setFormatCode('0.00%');

                for ($row = $dataStartRow; $row <= $lastRow; $row++) {
                    $sumPlanParts = [];
                    $sumRealParts = [];

                    for ($column = 2; $column < $totalStartColumn; $column += 3) {
                        $planColumn = $this->columnLetter($column);
                        $realColumn = $this->columnLetter($column + 1);
                        $deviationColumn = $this->columnLetter($column + 2);

                        $sheet->setCellValue("{$deviationColumn}{$row}", "={$realColumn}{$row}-{$planColumn}{$row}");
                        $sumPlanParts[] = "{$planColumn}{$row}";
                        $sumRealParts[] = "{$realColumn}{$row}";
                    }

                    if ($sumPlanParts !== [] && $sumRealParts !== []) {
                        $sheet->setCellValue("{$totalPlanColumn}{$row}", '=' . implode('+', $sumPlanParts));
                        $sheet->setCellValue("{$totalRealColumn}{$row}", '=' . implode('+', $sumRealParts));
                    }

                    $sheet->setCellValue("{$totalDeviationColumn}{$row}", "={$totalRealColumn}{$row}-{$totalPlanColumn}{$row}");
                    $sheet->setCellValue("{$percentageColumn}{$row}", '=IF(' . $totalPlanColumn . $row . '>0,(' . $totalPlanColumn . $row . '-' . $totalRealColumn . $row . ')/' . $totalPlanColumn . $row . ',"")');
                }

                $column = 4;
                while ($column <= (1 + (count($this->meses) * 3) + 3)) {
                    $deviationColumn = $this->columnLetter($column);
                    $sheet->getStyle("{$deviationColumn}5:{$deviationColumn}{$lastRow}")->getFont()->getColor()->setRGB('D11A2A');
                    $column += 3;
                }
            },
        ];
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