<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Categoria;
use App\Models\User;

class Productos extends Model
{
    use HasFactory;

    //nombre de la tabla 
    protected $table = 'productos';

    protected $fillable = [
        'codigo_sap',
        'nombre_producto',
        'ingrediente_activo',
        'categoria',
        'unidad_medida',
        'cantidad',
        'lote',
        'precio_compra',
        'proveedor',
        'numero_factura',
        'fecha_vencimiento',
        'imagen_producto'
    ];
    //Casting de tipos
        protected $casts = [
        'stock_actual'   => 'decimal:3',
        'stock_minimo'   => 'decimal:3',
        'precio_unitario'=> 'decimal:2',
        'ind_activo'     => 'boolean',
    ];

     // Producto pertenece a una categoría
    public function categoria()
    {
        return $this->belongsTo(Categorias::class, 'categoria_id');
    }

    // Producto fue creado por un usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

        // Solo productos activos
    public function scopeActivos($query)
    {
        return $query->where('ind_activo', true);
    }

    // Productos con stock bajo
    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo');
    }

    // Texto legible del estado
    public function getEstadoTextoAttribute()
    {
        return $this->ind_activo ? 'Activo' : 'Inactivo';
    }

    // Precio con moneda
    public function getPrecioFormateadoAttribute()
    {
        return agro_number($this->precio_unitario, 2) . ' ' . $this->moneda;
    }
}
