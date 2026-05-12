<?php

namespace App\Exports;

use App\Models\planes_cultivo;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PlanCultivoExport implements FromArray, ShouldAutoSize
{
    public function __construct(private readonly planes_cultivo $plan)
    {
    }

    public function array(): array
    {
        $cultivo = $this->plan->cultivo;
        $total = 0;

        $filas = $this->plan->detalles
            ->sortBy([
                ['semana', 'asc'],
                ['categoria', 'asc'],
            ])
            ->values()
            ->map(function ($detalle) use (&$total) {
                $subtotal = (float) ($detalle->subtotal ?? ($detalle->cantidad_estimada * $detalle->costo_unitario));
                $total += $subtotal;

                return [
                    'Semana ' . $detalle->semana,
                    $detalle->categoria,
                    $this->formatearActividad($detalle->categoria, $detalle->descripcion),
                    (float) $detalle->cantidad_estimada,
                    $detalle->unidad_medida,
                    (float) $detalle->costo_unitario,
                    $subtotal,
                ];
            })
            ->values()
            ->all();

        return array_merge([
            ['Plan de Cultivo', '#' . $this->plan->id],
            ['Cultivo', $cultivo?->nombre ?? '-'],
            ['Fecha Plan', $this->plan->fecha_plan],
            ['Cosecha Estimada', (float) ($cultivo?->cosecha_estimada ?? $this->plan->cosecha_estimada ?? 0)],
            ['Estado', $this->plan->estado],
            [],
            [
                'Semana Cultivo',
                'Categoria',
                'Actividad',
                'Cantidad',
                'Unidad',
                'Costo Unitario',
                'Subtotal',
            ],
        ], $filas, [[
            '',
            '',
            '',
            '',
            '',
            'TOTAL PRESUPUESTO',
            $total,
        ]]);
    }

    private function formatearActividad(?string $categoria, ?string $descripcion): string
    {
        $actividad = trim((string) $descripcion);
        $categoriaNormalizada = mb_strtolower(trim((string) $categoria), 'UTF-8');

        if (in_array($categoriaNormalizada, ['preparacion de suelo', 'preparación de suelo'], true)) {
            $actividad = preg_replace('/^mecanizaci[oó]n\s*[-:]\s*/iu', '', $actividad) ?? $actividad;
        }

        return $actividad === '' ? '-' : $actividad;
    }
}