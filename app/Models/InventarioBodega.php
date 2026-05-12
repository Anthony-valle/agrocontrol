<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Insumo;
use App\Models\Bodega;

class InventarioBodega extends Model
{
    use HasFactory, EmpresaScope;

    protected $table = 'inventario_bodegas';

    protected $fillable = [
        'empresa_id',
        'insumo_id',
        'bodega_id',
        'stock_actual',
        'costo_promedio',
        'numero_lote',
        'fecha_fabricacion',
        'fecha_vencimiento'
    ];

    // Relación con Insumo
    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }

    // Relación con Bodega
    public function bodega()
    {
        return $this->belongsTo(Bodega::class);
    }

    // Movimientos asociados a este inventario
    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'insumo_id', 'insumo_id')
                    ->whereColumn('bodega_origen_id', 'bodega_id')
                    ->orWhereColumn('bodega_destino_id', 'bodega_id');
    }
}