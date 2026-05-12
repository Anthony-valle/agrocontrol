<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insumos extends Model
{
    protected $fillable = [
        'categoria_id',
        'codigo',
        'nombres',
        'ingredientes_activo',
        'unidad_medida',
        'costo_estimado',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categorias::class,'categorias');
    }

    public function inventario()
    {
        return $this->hasMany(Inventario::class, 'inventario');
    }
}
