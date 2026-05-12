<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CultivoHistorialExport implements WithMultipleSheets
{
    protected $cultivo;

    public function __construct($cultivo)
    {
        $this->cultivo = $cultivo;
    }

    public function sheets(): array
    {
        return [
            new CultivoHistorialSummarySheet($this->cultivo),
            new CultivoHistorialDetailsSheet($this->cultivo),
        ];
    }
}
