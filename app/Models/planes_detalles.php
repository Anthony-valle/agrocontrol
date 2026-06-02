<?php

namespace App\Models;

use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class planes_detalles extends Model
{
    use HasFactory, SoftDeletes, TracksDeletionMetadata;

    protected $table = 'planes_detalles';

    protected $fillable = [
        'plan_cultivo_id',
        'semana',
        'categoria',
        'descripcion',
        'cantidad_estimada',
        'unidad_medida',
        'costo_unitario',
        'subtotal',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'cantidad_estimada' => 'decimal:3',
        'costo_unitario' => 'decimal:3',
        'subtotal' => 'decimal:3',
        'deleted_at' => 'datetime',
    ];


    public function plan()
    {
        return $this->belongsTo(planes_cultivo::class,'plan_cultivo_id');
    }
}