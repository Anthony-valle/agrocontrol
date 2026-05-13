<?php

namespace App\Exports;

use App\Models\Cultivo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CultivoCategoriaConsumosExport implements FromArray, ShouldAutoSize
{
    public function __construct(
        private readonly Cultivo $cultivo,
        private readonly string $categoria,
        private readonly Collection $detalles,
    ) {
    }

    public function array(): array
    {
        $filas = $this->detalles->map(function ($detalle) {
            $esManoObra = $this->categoria === 'Mano De Obra';

            return [
                $detalle->fecha ? \Carbon\Carbon::parse($detalle->fecha)->format('d/m/Y') : '-',
                $detalle->consumo_id,
                $detalle->estado,
                $esManoObra ? '-' : ($detalle->codigo ?? '-'),
                $esManoObra ? '-' : ($detalle->insumo ?? '-'),
                $detalle->bodega ?? '-',
                $esManoObra ? '-' : ($detalle->lote ?? '-'),
                $detalle->categoria ?? '-',
                $detalle->descripcion ?? '-',
                (float) ($detalle->cantidad ?? 0),
                $detalle->unidad_medida ?? '-',
                (float) ($detalle->costo_unitario ?? 0),
                (float) ($detalle->subtotal ?? 0),
            ];
        })->values()->all();

        return array_merge([
            ['Detalle de Consumos por Categoria'],
            ['Cultivo', $this->cultivo->nombre],
            ['Categoria', $this->categoria],
            [],
            ['Fecha', 'Consumo', 'Estado', 'Codigo', 'Insumo', 'Bodega', 'Lote', 'Categoria', 'Descripcion', 'Cantidad', 'Unidad', 'Costo Unitario', 'Subtotal'],
        ], $filas, [[
            '', '', '', '', '', '', '', '', '', '', '', 'TOTAL', (float) $this->detalles->sum('subtotal'),
        ]]);
    }
}
