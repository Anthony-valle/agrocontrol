<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provedor extends Model
{
    protected $fillable = [
        'nombre',
        'telefono',
        'email',
        'direccion',
        'estado'
    ];

    public function ordenes()
    {
        return $this->hasMany(Orden_compra::class);
    }
}
