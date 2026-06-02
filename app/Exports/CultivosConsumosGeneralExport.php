<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CultivosConsumosGeneralExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Collection $resumenCultivos,
        private readonly array $resumenGeneral,
        private readonly array $detallesCultivos,
    ) {
    }

    public function sheets(): array
    {
        $sheets = [
            new CultivosConsumosGeneralSummarySheet($this->resumenCultivos, $this->resumenGeneral),
        ];

        foreach ($this->detallesCultivos as $detalleCultivo) {
            $sheets[] = new CultivosConsumosGeneralDetailSheet(
                $detalleCultivo['sheet_name'],
                null,
                $detalleCultivo['cultivo'],
                $detalleCultivo['meses'],
                $detalleCultivo['filas'],
                $detalleCultivo['totales'],
            );
        }

        if (count($sheets) === 1) {
            $sheets[] = new CultivosConsumosGeneralDetailSheet('Detalle', null, null, [], collect(), [
                'meses' => [],
                'plan' => 0.0,
                'real' => 0.0,
                'desviacion' => 0.0,
                'porcentaje' => null,
            ]);
        }

        return $sheets;
    }
}
