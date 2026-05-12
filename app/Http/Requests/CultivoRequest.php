<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CultivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'lotes_id' => 'required|exists:lotes,id',
            'variedad' => 'required|string|max:255',
            'ciclo' => 'required|string|max:255',
            'fecha_siembra' => 'required|date',
            'duracion_ciclo' => 'required|integer|min:1',
            'hectareas' => 'nullable|numeric|min:0',
            'cosecha_estimada' => 'nullable|numeric|min:0',
            'unidad_medida' => 'required|string|max:50',
            'estado' => 'required|string|max:50',
            'observaciones' => 'nullable|string',
        ];
    }
}