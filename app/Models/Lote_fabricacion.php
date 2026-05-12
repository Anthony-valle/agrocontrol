<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lote_fabricacion extends Model
{
    use HasFactory;

    protected $table = 'lote_fabricacions';

    protected $fillable = [
        'insumo_id',
        'bodega_id',
        'numero_lote',
        'fecha_fabricacion',
        'fecha_vencimiento',
        'stock_actual'
    
    ];

    public function insumo() {
        return $this->belongsTo(Insumo::class);
    }

    public function bodega() {
        return $this->belongsTo(Bodega::class);
    }

    public function movimientos() {
        return $this->hasMany(MovimientoInventario::class, 'lote_id');
    }
}
