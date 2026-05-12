<?php

namespace App\Imports;

use App\Models\Insumos;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;

class InsumosImport implements ToModel, WithHeadingRow, WithValidation
{
    use Importable;

    public function model(array $row)
    {
        return new Insumos([
            'categoria_id'          => $row['categoria_id'],
            'codigo'                => $row['codigo'],
            'nombres'               => $row['nombres'],
            'ingredientes_activo'   => $row['ingredientes_activo'] ?? null,
            'unidad_medida'         => $row['unidad_medida'],
            'costo_estimado'        => (float) $row['costo_estimado'],
        ]);
    }

    public function rules(): array
    {
        return [
            'categoria_id'    => 'required|exists:categorias,id',
            'codigo'          => 'required|unique:insumos,codigo',
            'nombres'         => 'required',
            'unidad_medida'   => 'required',
            'costo_estimado'  => 'required|numeric',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'categoria_id.required'   => 'La categoría es obligatoria.',
            'categoria_id.exists'     => 'La categoría no existe en el sistema.',
            'codigo.required'         => 'El código es obligatorio.',
            'codigo.unique'           => 'El código ya existe en el catálogo.',
            'nombres.required'        => 'El nombre del insumo es obligatorio.',
            'unidad_medida.required'  => 'La unidad de medida es obligatoria.',
            'costo_estimado.required' => 'El costo estimado es obligatorio.',
            'costo_estimado.numeric'  => 'El costo estimado debe ser un número.',
        ];
    }
}
