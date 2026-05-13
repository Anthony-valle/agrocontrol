<?php

namespace App\Exports;

use App\Models\Cultivo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
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

class CultivoHistorialDetailsSheet implements FromArray, ShouldAutoSize, WithDrawings, WithEvents, WithStyles, WithTitle
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
        $rows = $this->buildRows()->all();

        return array_merge([
            ['Reporte de Consumos - ' . $this->cultivo->nombre],
            ['Generado', now()->format('d/m/Y H:i')],
            ['Fecha', 'Lote', 'Cultivo', 'Categoria', 'Codigo', 'Insumo', 'Concepto', 'Lote consumido', 'Ingrediente activo', 'Cantidad', 'Unidad', 'Subtotal'],
        ], $rows, [[
            '', '', '', '', '', '', '', '', '', '', 'TOTAL', (float) $this->consumos->flatMap(fn ($consumo) => $consumo->detalles)->sum('subtotal'),
        ]]);
    }

    private function buildRows(): Collection
    {
        return $this->consumos->flatMap(function ($consumo) {
            return $consumo->detalles->map(function ($detalle) use ($consumo) {
                $insumo = $detalle->insumo;
                $categoria = $this->normalizarCategoria((string) ($detalle->categoria ?? ''));
                $esRegistroConceptual = mb_strtolower($categoria) === 'mano de obra' || !$insumo;
                $descripcion = trim((string) ($detalle->descripcion ?? ''));

                return [
                    'categoria_orden' => mb_strtolower($categoria),
                    'fecha_orden' => $consumo->fecha_consumo ? \Carbon\Carbon::parse($consumo->fecha_consumo)->format('Y-m-d') : '',
                    $consumo->fecha_consumo ? \Carbon\Carbon::parse($consumo->fecha_consumo)->format('d/m/Y') : '-',
                    optional($this->cultivo->lote)->nombre ?? '-',
                    $this->cultivo->nombre,
                    $categoria,
                    $esRegistroConceptual ? '-' : ($insumo->codigo ?? '-'),
                    $esRegistroConceptual ? '-' : ($insumo->nombre ?? '-'),
                    $descripcion !== '' ? $descripcion : '-',
                    $esRegistroConceptual ? '-' : ($detalle->lote ?: '-'),
                    $esRegistroConceptual ? '-' : ($insumo->ingrediente_activo ?? $insumo->ingredientes_activo ?? '-'),
                    (float) ($detalle->cantidad ?? 0),
                    $detalle->unidad_medida ?? $insumo->unidad_medida ?? '-',
                    (float) ($detalle->subtotal ?? 0),
                ];
            });
        })->sort(function (array $left, array $right) {
            $categoria = strcmp($left['categoria_orden'], $right['categoria_orden']);
            if ($categoria !== 0) {
                return $categoria;
            }

            return strcmp($right['fecha_orden'], $left['fecha_orden']);
        })->values()->map(function (array $row) {
            unset($row['categoria_orden'], $row['fecha_orden']);

            return array_values($row);
        });
    }

    private function normalizarCategoria(string $categoria): string
    {
        $categoria = trim($categoria);

        if ($categoria === '') {
            return 'Otros Insumos';
        }

        if (mb_strtolower($categoria) === 'mano de obra') {
            return 'Mano de Obra';
        }

        return $categoria;
    }

    public function title(): string
    {
        return 'Detalle';
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
        $totalRow = 4 + $this->consumos->flatMap(fn ($consumo) => $consumo->detalles)->count();

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
                $totalRow = 4 + $this->consumos->flatMap(fn ($consumo) => $consumo->detalles)->count();

                $sheet->mergeCells('B1:L1');
                $sheet->mergeCells('B2:L2');
                $sheet->setCellValue('A1', '');
                $sheet->setCellValue('A2', '');
                $sheet->setCellValue('B1', 'Reporte de Consumos - ' . $this->cultivo->nombre);
                $sheet->setCellValue('B2', 'Generado: ' . now()->format('d/m/Y H:i'));
                $sheet->freezePane('A4');

                $sheet->getRowDimension(1)->setRowHeight(48);
                $sheet->getRowDimension(2)->setRowHeight(24);

                $sheet->getStyle("A3:L{$totalRow}")->applyFromArray([
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

                $sheet->getStyle('J4:J' . $totalRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle('L4:L' . $totalRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle('J4:J' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('L4:L' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('A1:L2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('B1:L1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('B2:L2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            },
        ];
    }
}
