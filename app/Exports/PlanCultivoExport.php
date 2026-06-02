<?php

namespace App\Exports;

use App\Models\planes_cultivo;
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

class PlanCultivoExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithEvents
{
    public function __construct(private readonly planes_cultivo $plan)
    {
    }

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
        $cultivo = $this->plan->cultivo;
        $hectareasCultivo = (float) ($cultivo?->hectareas ?? 0);

        return $this->plan->detalles
            ->sortBy([
                ['semana', 'asc'],
                ['categoria', 'asc'],
                ['descripcion', 'asc'],
            ])
            ->values()
            ->map(function ($detalle) use ($cultivo, $hectareasCultivo) {
                $cantidadTotal = (float) ($detalle->cantidad_estimada ?? 0);
                $cantidadBasePorHa = $hectareasCultivo > 0
                    ? round($cantidadTotal / $hectareasCultivo, 3)
                    : round($cantidadTotal, 3);

                return [
                    (int) ($cultivo?->id ?? $this->plan->cultivo_id),
                    (string) ($cultivo?->codigo ?? ''),
                    (string) ($cultivo?->nombre ?? ''),
                    (string) $this->plan->fecha_plan,
                    (int) ($detalle->semana ?? 0),
                    $detalle->categoria,
                    trim((string) $detalle->descripcion),
                    $cantidadBasePorHa,
                    $detalle->unidad_medida,
                    (float) $detalle->costo_unitario,
                ];
            })
            ->values()
            ->all();
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
                $lastRow = max(1, count($this->array()) + 1);

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
                $sheet->getStyle("A1:J1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A1:J1")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}