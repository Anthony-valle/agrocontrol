<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    use HasFactory;

    protected $table = 'inventario';

    protected $fillable = [
        'insumo_id',
        'bodega_id',
        'stock_actual',
        'costo_promedio'
    ];

    public function insumo() {
        return $this->belongsTo(Insumo::class);
    }

    public function bodega() {
        return $this->belongsTo(Bodega::class);
    }
}
