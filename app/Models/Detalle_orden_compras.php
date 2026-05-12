<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Detalle_orden_compras extends Model
{
    protected $table = 'Detalle_orden_compras';

    protected $fillable = [
        'orden_compras_id',
        'insumos_id',
        'cantidad',
        'precio_unitario',
        'sub_total'
    ];

    public function insumo()
    {
        return $this->belongsTo(Insumos::class);
    }
}
