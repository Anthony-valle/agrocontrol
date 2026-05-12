<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'usuario_id',
        'codigo',
        'nombre_insumos',
        'ingrediente_activo',
        'uom_base',
        'stock_actual',
        'stock_minimo',
        'precio_unitario',
        'moneda',
        'ind_activo'
    ];

    // 🔗 Relaciones
    public function categoria()
    {
        return $this->belongsTo(Categorias::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function entradas()
    {
        return $this->hasMany(Entrada::class);
    }

    public function salidas()
    {
        return $this->hasMany(Salidas::class);
    }
}
