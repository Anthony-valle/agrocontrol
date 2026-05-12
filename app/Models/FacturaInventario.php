<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\MovimientoInventario;
use App\Models\Insumo;
use App\Models\Bodega;
use App\Models\User;

class FacturaInventario extends Model
{
    use HasFactory, EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    protected $table = 'factura_inventarios';

    protected $fillable = [
        'empresa_id',
        'movimiento_id',
        'insumo_id',
        'bodega_id',
        'cantidad',
        'precio_unitario',
        'total',
        'proveedor',
        'numero_lote',
        'fecha_fabricacion',
        'fecha_vencimiento',
        'archivo',
        'created_by'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function movimiento() {
        return $this->belongsTo(MovimientoInventario::class, 'movimiento_id');
    }

    public function insumo() {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    public function bodega() {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    public function creador() {
        return $this->belongsTo(User::class, 'created_by');
    }
}