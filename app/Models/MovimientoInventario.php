<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Insumo;
use App\Models\Bodega;
use App\Models\FacturaInventario;
use App\Models\User;

class MovimientoInventario extends Model
{
    use HasFactory, EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    protected $table = 'movimiento_inventarios';

    protected $fillable = [
        'empresa_id',
        'insumo_id',
        'bodega_origen_id',
        'bodega_destino_id',
        'tipo',
        'cantidad',
        'precio_unitario',
        'costo_unitario',
        'stock_anterior',
        'stock_actual',
        'descripcion',
        'referencia',
        'numero_lote',
        'fecha_fabricacion',
        'fecha_vencimiento',
        'sucursal_id',
        'consumo_id',
        'created_by'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // Relación con insumo
    public function insumo() 
    {
        return $this->belongsTo(Insumo::class);
    }

    // Bodega origen
    public function bodegaOrigen() 
    {
        return $this->belongsTo(Bodega::class, 'bodega_origen_id');
    }

    // Bodega destino
    public function bodegaDestino() 
    {
        return $this->belongsTo(Bodega::class, 'bodega_destino_id');
    }

    // Facturas asociadas
    public function facturas() 
    {
        return $this->hasMany(FacturaInventario::class, 'movimiento_id');
    }

    public function consumo()
    {
       return $this->belongsTo(Consumo::class, 'consumo_id');
    }

    // Usuario creador
    public function creador() 
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Usuario editor (opcional)
    public function editor() 
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}