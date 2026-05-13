<?php

namespace App\Exports;

use App\Exports\ConsumosCategoriaSheet;
use App\Exports\ConsumosResumenSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ConsumosFiltradosExport implements WithMultipleSheets
{
    public function __construct(private readonly Collection $consumos)
    {
    }

    public function sheets(): array
    {
        $registros = $this->buildRegistros();
        $agrupados = $registros->groupBy('categoria');

        $sheets = [new ConsumosResumenSheet($registros)];

        foreach ($agrupados as $categoria => $items) {
            $sheets[] = new ConsumosCategoriaSheet($categoria, $items);
        }

        if (count($sheets) === 1) {
            $sheets[] = new ConsumosCategoriaSheet('General', collect());
        }

        return $sheets;
    }

    private function buildRegistros(): Collection
    {
        return $this->consumos->flatMap(function ($consumo) {
            return $consumo->detalles->map(function ($detalle) use ($consumo) {
                $categoria = $this->normalizarCategoria($detalle->categoria ?? null);
                $esManoDeObra = mb_strtolower($categoria) === 'mano de obra';

                return [
                    'fecha' => optional($consumo->fecha_consumo)
                        ? \Carbon\Carbon::parse($consumo->fecha_consumo)->format('d/m/Y')
                        : '-',
                    'lote' => $consumo->cultivo->lote->nombre ?? '-',
                    'cultivo' => $consumo->cultivo->nombre ?? '-',
                    'codigo' => $esManoDeObra ? '-' : ($detalle->insumo->codigo ?? '-'),
                    'insumo_concepto' => $esManoDeObra
                        ? trim((string) ($detalle->descripcion ?? 'Mano de Obra'))
                        : ($detalle->insumo->nombre ?? $detalle->descripcion ?? '-'),
                    'lote_consumido' => $esManoDeObra ? '-' : ($detalle->lote ?? '-'),
                    'ingrediente_activo' => $esManoDeObra
                        ? '-'
                        : ($detalle->insumo->ingrediente_activo ?? $detalle->insumo->ingredientes_activo ?? '-'),
                    'categoria' => $categoria,
                    'cantidad' => (float) ($detalle->cantidad ?? 0),
                    'unidad' => $detalle->unidad_medida ?? '-',
                    'subtotal' => (float) ($detalle->subtotal ?? 0),
                ];
            });
        })->values();
    }

    private function normalizarCategoria(?string $categoria): string
    {
        $categoria = trim((string) $categoria);

        if ($categoria === '') {
            return 'General';
        }

        if (mb_strtolower($categoria) === 'mano de obra') {
            return 'Mano de Obra';
        }

        return $categoria;
    }
}
