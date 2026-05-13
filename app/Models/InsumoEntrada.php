<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsumoEntrada extends Model
{
    use HasFactory;

    protected $table = 'insumo_entradas';

    protected $fillable = [
        'insumo_id',
        'bodega_id',
        'tipo',
        'cantida',
        'costo_unitario',
        'factura',
        'proveedor',
        'fecha_ingreso',
        'created_by',
        'updated_by',
    ];

    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
