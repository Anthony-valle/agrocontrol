<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sucursal_id' => 'required|integer',
            'codigo' => 'required|string|max:255',
            'area' => 'required|numeric|min:0',
            'nombre' => 'nullable|string|max:255',
            'estado' => 'required|in:0,1',
            'poligono' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'sucursal_id.integer' => 'La sucursal seleccionada no es valida.',
            'codigo.required' => 'El codigo es obligatorio.',
            'area.required' => 'El area es obligatoria.',
            'area.numeric' => 'El area debe ser numerica.',
            'estado.required' => 'El estado es obligatorio.',
        ];
    }
}