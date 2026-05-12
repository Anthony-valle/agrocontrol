<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orden_compra extends Model
{
    protected $table ='Orden_compras';

    protected $fillable = [
        'proveedor_id',
        'fecha_orden',
        'total',
        'estado',
        'observacion'
    ];

    public function proveedor()
    {
        return $this->belongsTo(Provedor::class);
    }

    public function detalles()
    {
        return $this->hasMany(Detalle_orden_compras::class);
    }

}
