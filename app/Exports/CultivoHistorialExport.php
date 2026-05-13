<?php

namespace App\Exports;

use App\Models\Cultivo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CultivoHistorialExport implements WithMultipleSheets
{
    protected Cultivo $cultivo;

    protected Collection $consumos;

    public function __construct(Cultivo $cultivo, Collection $consumos)
    {
        $this->cultivo = $cultivo;
        $this->consumos = $consumos;
    }

    public function sheets(): array
    {
        return [
            new CultivoHistorialSummarySheet($this->cultivo, $this->consumos),
            new CultivoHistorialDetailsSheet($this->cultivo, $this->consumos),
        ];
    }
}
