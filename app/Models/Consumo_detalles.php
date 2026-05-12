<?php

namespace App\Models;

use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consumo_detalles extends Model
{
    use HasFactory, SoftDeletes, TracksDeletionMetadata;

    protected $table = 'consumo_detalles';

    protected $fillable = [
        'consumo_id',
        'categoria',
        'descripcion',
        'cantidad',
        'unidad_medida',
        'costo_unitario',
        'subtotal',
        'insumo_id',
        'bodega_id',
        'lote',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function cultivo(){
        return $this->belongsTo(Cultivo::class);
    }

    public function insumo(){
        return $this->belongsTo(Insumo::class);
    }

    public function bodega(){
        return $this->belongsTo(Bodega::class);
    }
}
