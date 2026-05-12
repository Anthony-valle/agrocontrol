<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Lotes extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_lote',
        'ubicacion',
        'tamaño',
        'tipo_suelo'
    ];

     // Relación: categoría creada por un usuario
    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
