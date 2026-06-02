<?php

namespace App\Exports;

use App\Models\Cultivo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CultivoHistorialSummarySheet implements FromArray, ShouldAutoSize, WithDrawings, WithEvents, WithStyles, WithTitle
{
    protected Cultivo $cultivo;

    protected Collection $consumos;

    public function __construct(Cultivo $cultivo, Collection $consumos)
    {
        $this->cultivo = $cultivo;
        $this->consumos = $consumos;
    }

    public function array(): array
    {
        $consumos = $this->consumos->sortByDesc('fecha_consumo');
        $totalConsumo = (float) $consumos->sum('total');
        $totalConsumos = $consumos->count();

        $categoryTotals = $consumos->flatMap(function ($consumo) {
            return $consumo->detalles->map(function ($detalle) {
                return [
                    'categoria' => (string) ($detalle->categoria ?: 'Sin categoría'),
                    'cantidad' => (float) ($detalle->cantidad ?? 0),
                    'subtotal' => (float) ($detalle->subtotal ?? 0),
                ];
            });
        })->groupBy('categoria')->map(function ($items) {
            return [
                'categoria' => $items->first()['categoria'],
                'registros' => $items->count(),
                'cantidad' => (float) $items->sum('cantidad'),
                'subtotal' => (float) $items->sum('subtotal'),
            ];
        })->values();

        return array_merge([
            ['Reporte de Historial de Consumos - ' . $this->cultivo->nombre],
            ['Generado', now()->format('d/m/Y H:i')],
            ['Categoria', 'Registros', 'Cantidad Total', 'Subtotal'],
        ], $categoryTotals->map(fn (array $total) => [
            $total['categoria'],
            (float) $total['registros'],
            $total['cantidad'],
            $total['subtotal'],
        ])->all(), [[
            'TOTAL',
            (float) $totalConsumos,
            (float) $categoryTotals->sum('cantidad'),
            $totalConsumo,
        ]]);
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
        $totalRow = 4 + $this->consumos->flatMap(fn ($consumo) => $consumo->detalles)->groupBy(fn ($detalle) => (string) ($detalle->categoria ?: 'Sin categoría'))->count();

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
                $totalRow = 4 + $this->consumos->flatMap(fn ($consumo) => $consumo->detalles)->groupBy(fn ($detalle) => (string) ($detalle->categoria ?: 'Sin categoría'))->count();

                $sheet->mergeCells('B1:D1');
                $sheet->mergeCells('B2:D2');
                $sheet->setCellValue('A1', '');
                $sheet->setCellValue('A2', '');
                $sheet->setCellValue('B1', 'Reporte de Historial de Consumos - ' . $this->cultivo->nombre);
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
                $sheet->getStyle("C4:D{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.000');
                $sheet->getStyle("B4:D{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
