<?php

namespace App\Imports;

use App\Models\Insumos;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class Insumos_importar implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new Insumos([
            'categoria_id'          => $row['categoria_id'],
            'codigo'                => $row['codigo'],
            'nombres'               => $row['nombres'],
            'ingredientes_activo'   => $row['ingredientes_activo'],
            'unidad_medida'         => $row['unidad_medida'],
            'costo_estimado'        => $row['costo_estimado'],
        ]);
    }

    public function rules(): array
    {
        return [
            'categoria_id'        => 'required|exists:categorias,id',
            'codigo'              => 'required',
            'nombres'             => 'required',
            'unidad_medida'       => 'required',
            'costo_estimado'      => 'required|numeric',
        ];
    }
}
