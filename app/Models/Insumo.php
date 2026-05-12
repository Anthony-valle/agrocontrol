<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\InventarioBodega;
use App\Models\Sucursale;
use App\Models\User;

class Insumo extends Model
{
    use HasFactory, EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    protected $table = 'insumos';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'ingrediente_activo',
        'ingredientes_activo',
        'categoria_id',
        'categoria_nombre',
        'unidad_medida',
        'costo_estimado',
        'stock_minimo',
        'estado',
        'sucursal_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // Relación con categoría (si tienes tabla categorías)
    public function categoria() {
        return $this->belongsTo(Categorias::class, 'categoria_nombre', 'nombre');
    }

    // Relación con sucursal
    public function sucursal() {
        return $this->belongsTo(Sucursale::class, 'sucursal_id');
    }

    // Relación con inventarios por bodega
    public function inventarioBodegas() {
        return $this->hasMany(InventarioBodega::class);
    }

    // Movimientos de inventario
    public function movimientos() {
        return $this->hasMany(MovimientoInventario::class);
    }

    // Usuarios creador y editor
    public function creador() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor() {
        return $this->belongsTo(User::class, 'updated_by');
    }
}