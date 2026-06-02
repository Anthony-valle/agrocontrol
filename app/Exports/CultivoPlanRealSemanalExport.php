<?php

namespace App\Exports;

use App\Models\Cultivo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CultivoPlanRealSemanalExport implements FromArray, ShouldAutoSize
{
    public function __construct(
        private readonly Cultivo $cultivo,
        private readonly mixed $plan,
        private readonly Collection $comparaciones,
        private readonly Collection $resumenEstados,
        private readonly Collection $resumenSemanas,
    ) {
    }

    public function array(): array
    {
        $planId = $this->plan?->id ? '#' . $this->plan->id : 'Sin plan';
        $planFecha = $this->plan?->fecha_plan ?? 'N/D';

        $filasComparacion = $this->comparaciones->map(function (array $fila) {
            return [
                $fila['semana'] > 0 ? $fila['semana'] : 'Sin semana',
                $fila['categoria'],
                $fila['concepto'],
                (float) $fila['cantidad_plan'],
                (float) $fila['cantidad_real'],
                $fila['unidad_medida'],
                (float) $fila['costo_plan'],
                (float) $fila['costo_real'],
                (float) $fila['diferencia_cantidad'],
                (float) $fila['diferencia_costo'],
                $fila['estado'],
            ];
        })->all();

        $filasEstado = $this->resumenEstados->map(function (array $fila) {
            return [
                $fila['estado'],
                (float) $fila['registros'],
                (float) $fila['costo_plan'],
                (float) $fila['costo_real'],
            ];
        })->all();

        $filasSemana = $this->resumenSemanas->map(function (array $fila) {
            return [
                $fila['semana'] > 0 ? $fila['semana'] : 'Sin semana',
                (float) $fila['registros'],
                (float) $fila['pendientes'],
                (float) $fila['no_planificados'],
                (float) $fila['desvios'],
                (float) $fila['costo_plan'],
                (float) $fila['costo_real'],
            ];
        })->all();

        return array_merge([
            ['Reporte Plan vs Real Semanal'],
            ['Cultivo', $this->cultivo->nombre],
            ['Plan', $planId],
            ['Fecha plan', $planFecha],
            ['Generado', now()->format('d/m/Y H:i')],
            [],
            ['Resumen por estado'],
            ['Estado', 'Registros', 'Costo plan', 'Costo real'],
        ], $filasEstado, [
            [],
            ['Resumen por semana'],
            ['Semana', 'Registros', 'Plan sin real', 'Real sin plan', 'Desvios', 'Costo plan', 'Costo real'],
        ], $filasSemana, [
            [],
            ['Detalle comparativo'],
            ['Semana', 'Categoria', 'Concepto', 'Cantidad plan', 'Cantidad real', 'Unidad', 'Costo plan', 'Costo real', 'Diferencia cantidad', 'Diferencia costo', 'Estado'],
        ], $filasComparacion);
    }
}